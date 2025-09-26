document.addEventListener('DOMContentLoaded', () => {
    // Element references
    const selMinTier = document.getElementById('sel-min-tier'); // Version filter in main.blade.php & equipment.blade.php
    const selVersion = document.getElementById('sel-version'); // Tier filter in main.blade.php & equipment.blade.php
    const inputPickRate = document.getElementById('input-pick-rate');
    const gameTable = document.getElementById("gameTable");
    const tableBody = gameTable?.querySelector("tbody");

    // Equipment page specific filters
    const selItemGrade = document.getElementById('sel-item-grade');
    const selItemType2 = document.getElementById('sel-item-type2');

    // Modal elements
    const tierModal = document.getElementById('tierModal');
    const openTierModalBtn = document.getElementById('openTierModal');
    const closeButton = document.querySelector('.close-button');

    // --- Filter and URL Update Logic ---

    function updateUrlForVersionAndTier() {
        // This function is primarily for version and tier which cause a page reload.
        // Other filters (pick rate, item grade, item type2) are client-side.
        if (!selMinTier || !selVersion) return; // Only run if these primary filters exist

        let selectedVersion = selMinTier.value;
        let selectedMinTier = selVersion.value;

        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('min_tier', selectedMinTier);
        currentUrl.searchParams.set('version', selectedVersion);

        if (window.location.href !== currentUrl.href) {
            window.location.href = currentUrl.href;
        }
    }

    function applyAllFilters() {
        if (!tableBody && !tierModal) return;

        const minPickRate = parseFloat(inputPickRate?.value) || 0;
        const selectedGrade = selItemGrade?.value || 'All';
        const selectedType2 = selItemType2?.value || 'All';

        // Filter main table rows
        if (tableBody) {
            tableBody.querySelectorAll("tr").forEach(row => {
                let showRow = true;

                if (inputPickRate) {
                    const pickRateCell = row.cells[3];
                    if (pickRateCell) {
                        const pickRateText = pickRateCell.querySelector('div:first-child')?.innerText || '0%';
                        const pickRateValue = parseFloat(pickRateText.replace(/%/g, ''));
                        if (!isNaN(pickRateValue) && pickRateValue < minPickRate) {
                            showRow = false;
                        }
                    }
                }

                if (selItemGrade && selectedGrade !== 'All') {
                    if (row.dataset.itemGrade !== selectedGrade) {
                        showRow = false;
                    }
                }

                if (selItemType2 && selectedType2 !== 'All') {
                    if (row.dataset.itemType2 !== selectedType2) {
                        showRow = false;
                    }
                }

                row.style.display = showRow ? "" : "none";
            });
        }

        // Filter tier modal icons
        if (tierModal) {
            tierModal.querySelectorAll(".tier-character-icon-container").forEach(iconContainer => {
                let showIcon = true;

                const pickRateValue = parseFloat(iconContainer.dataset.pickRate) || 0;
                if (pickRateValue < minPickRate) {
                    showIcon = false;
                }

                if (selItemGrade && selectedGrade !== 'All') {
                    if (iconContainer.dataset.itemGrade !== selectedGrade) {
                        showIcon = false;
                    }
                }

                if (selItemType2 && selectedType2 !== 'All') {
                    if (iconContainer.dataset.itemType2 !== selectedType2) {
                        showIcon = false;
                    }
                }

                iconContainer.style.display = showIcon ? "inline-block" : "none";
            });
        }

        renumberRankColumn();
        sortTableByActiveHeader();
    }

    function renumberRankColumn() {
        if (!tableBody) return;
        let visibleRank = 1;
        tableBody.querySelectorAll("tr").forEach(row => {
            if (row.style.display !== "none") {
                if (row.cells[0]) {
                    row.cells[0].innerText = visibleRank++;
                }
            }
        });
    }

    // --- Event Listeners ---

    if (selMinTier) { // For version
        selMinTier.addEventListener('change', updateUrlForVersionAndTier);
    }
    if (selVersion) { // For tier
        selVersion.addEventListener('change', updateUrlForVersionAndTier);
    }
    if (inputPickRate) {
        inputPickRate.addEventListener('input', () => {
            localStorage.setItem('pickRateFilter', inputPickRate.value); // Save pick rate
            applyAllFilters();
        });
        // Load saved pick rate
        const savedPickRate = localStorage.getItem('pickRateFilter');
        if (savedPickRate) {
            inputPickRate.value = savedPickRate;
        }
    }
    if (selItemGrade) {
        selItemGrade.addEventListener('change', applyAllFilters);
    }
    if (selItemType2) {
        selItemType2.addEventListener('change', applyAllFilters);
    }

    // --- Table Sorting Logic ---
    const headers = gameTable?.querySelectorAll("th.sortable");

    headers?.forEach((header) => {
        header.addEventListener("click", () => {
            const isAscending = !header.classList.contains("asc");
            headers.forEach(h => h.classList.remove("asc", "desc", "active-sort"));
            header.classList.add(isAscending ? "asc" : "desc");
            header.classList.add("active-sort");
            sortTableByActiveHeader();
        });
    });

    function sortTableByActiveHeader() {
        const activeSortHeader = gameTable?.querySelector('th.sortable.active-sort');
        if (!activeSortHeader || !tableBody) return;

        const columnIndex = activeSortHeader.cellIndex;
        const isAscending = activeSortHeader.classList.contains("asc");
        const rowsToSort = Array.from(tableBody.querySelectorAll("tr:not([style*='display: none'])")); // Only sort visible rows

        rowsToSort.sort((rowA, rowB) => {
            let valA, valB;

            if (columnIndex === 2) { // Tier column
                valA = parseFloat(rowA.cells[columnIndex]?.dataset.score);
                valB = parseFloat(rowB.cells[columnIndex]?.dataset.score);
                valA = isNaN(valA) ? -Infinity : valA;
                valB = isNaN(valB) ? -Infinity : valB;
            } else {
                const cellA = rowA.cells[columnIndex]?.innerText.trim() || '';
                const cellB = rowB.cells[columnIndex]?.innerText.trim() || '';
                const numA = parseFloat(cellA.replace(/%/g, ''));
                const numB = parseFloat(cellB.replace(/%/g, ''));

                if (!isNaN(numA) && !isNaN(numB)) {
                    valA = numA;
                    valB = numB;
                } else {
                    valA = cellA;
                    valB = cellB;
                }
            }

            if (typeof valA === 'number' && typeof valB === 'number') {
                return isAscending ? valA - valB : valB - valA;
            } else {
                return isAscending
                    ? String(valA).localeCompare(String(valB), undefined, {numeric: true, sensitivity: 'base'})
                    : String(valB).localeCompare(String(valA), undefined, {numeric: true, sensitivity: 'base'});
            }
        });

        // Append sorted visible rows first, then hidden rows
        const hiddenRows = Array.from(tableBody.querySelectorAll("tr[style*='display: none']"));
        tableBody.innerHTML = "";
        rowsToSort.forEach(row => tableBody.appendChild(row));
        hiddenRows.forEach(row => tableBody.appendChild(row)); // Re-append hidden rows at the end

        renumberRankColumn(); // Renumber after sorting visible rows
    }

    // --- Row Click Navigation Logic ---
    tableBody?.addEventListener("click", (event) => {
        const row = event.target.closest("tr");
        if (row && row.dataset.href && row.style.display !== 'none') {
            window.location.href = row.dataset.href;
        }
    });

    // --- Modal Logic ---
    openTierModalBtn?.addEventListener('click', () => {
        if (tierModal) {
            tierModal.style.display = 'block';
        }
    });

    closeButton?.addEventListener('click', () => {
        if (tierModal) {
            tierModal.style.display = 'none';
        }
    });

    window.addEventListener('click', (event) => {
        if (event.target === tierModal) {
            tierModal.style.display = 'none';
        }
    });

    // --- Tooltip functionality for tier modal ---
    let activeTooltip = null; // Global reference to currently active tooltip

    function hideActiveTooltip() {
        if (activeTooltip && activeTooltip.element) {
            const isMobile = window.innerWidth <= 768;

            if (isMobile && activeTooltip.element.parentNode === document.body) {
                // Mobile: Clean up tooltip moved to body
                activeTooltip.element.style.visibility = 'hidden';
                activeTooltip.element.style.opacity = '0';

                // Move back to original container
                if (activeTooltip.originalContainer) {
                    activeTooltip.originalContainer.appendChild(activeTooltip.element);
                    activeTooltip.element.style.position = 'absolute';
                    activeTooltip.element.style.top = '';
                    activeTooltip.element.style.left = '';
                    activeTooltip.element.style.transform = 'translateX(-50%)';
                    activeTooltip.element.style.zIndex = '';
                }
            }

            // Remove active class from parent row (desktop)
            if (activeTooltip.parentRow) {
                activeTooltip.parentRow.classList.remove('tooltip-active');
            }

            activeTooltip = null;
        }
    }

    function initializeTierTooltips() {
        const tierModal = document.getElementById('tierModal');
        if (!tierModal) return;

        tierModal.querySelectorAll('.tier-character-icon-container').forEach(container => {
            // Create tooltip element if not exists
            let tooltip = container.querySelector('.tier-tooltip');
            if (!tooltip) {
                tooltip = document.createElement('div');
                tooltip.className = 'tier-tooltip';

                // Get data from container attributes
                const characterName = container.dataset.characterName || '';
                const weaponType = container.dataset.weaponType || '';
                const equipmentName = container.dataset.equipmentName || '';
                const tier = container.dataset.tier || '';
                const winRate = container.dataset.winRate || '0';
                const top2Rate = container.dataset.top2Rate || '0';
                const top4Rate = container.dataset.top4Rate || '0';
                const avgScore = container.dataset.avgScore || '0';
                const pickRate = container.dataset.pickRate || '0';

                // Build tooltip content based on page type
                let title = '';
                if (characterName && weaponType) {
                    // Main page (character + weapon)
                    title = `<strong>${characterName} ${weaponType}</strong>`;
                } else if (equipmentName) {
                    // Equipment pages
                    title = `<strong>${equipmentName}</strong>`;
                }

                tooltip.innerHTML = `
                    ${title}<br>
                    티어: <span style="color: #ffd700;">${tier}</span><br>
                    픽률: ${pickRate}%<br>
                    승률: ${winRate}%<br>
                    TOP2: ${top2Rate}%<br>
                    TOP4: ${top4Rate}%<br>
                    평균 점수: ${avgScore}
                `;

                container.appendChild(tooltip);
            }

            // Add dynamic positioning on hover
            const showTooltip = function(e) {
                // Hide any previously active tooltip
                hideActiveTooltip();

                const tooltip = this.querySelector('.tier-tooltip');
                if (!tooltip) return;

                const rect = this.getBoundingClientRect();
                const viewportHeight = window.innerHeight;
                const iconCenterY = rect.top + (rect.height / 2);
                const screenMiddle = viewportHeight / 2;
                const isMobile = window.innerWidth <= 768;

                // Set up active tooltip tracking
                const parentRow = this.closest('tr');
                activeTooltip = {
                    element: tooltip,
                    originalContainer: this,
                    parentRow: parentRow
                };

                // Add active class to parent row for z-index control (desktop)
                if (!isMobile && parentRow) {
                    parentRow.classList.add('tooltip-active');
                }

                if (isMobile) {
                    // Mobile: Move tooltip to body to avoid stacking context issues
                    document.body.appendChild(tooltip);
                    tooltip.style.position = 'fixed';
                    tooltip.style.zIndex = '99999';

                    // Show tooltip immediately for mobile to measure its size
                    tooltip.style.visibility = 'visible';
                    tooltip.style.opacity = '1';
                    tooltip.style.left = '0px'; // Temporary position for measurement
                    tooltip.style.top = '0px';

                    // Get actual tooltip dimensions after rendering
                    const tooltipRect = tooltip.getBoundingClientRect();
                    const tooltipWidth = tooltipRect.width;
                    const tooltipHeight = tooltipRect.height;

                    // Calculate centered position
                    const leftPos = rect.left + (rect.width / 2) - (tooltipWidth / 2);

                    if (iconCenterY < screenMiddle) {
                        // Upper half - show tooltip below
                        tooltip.style.top = (rect.bottom + 10) + 'px';
                        tooltip.classList.add('tooltip-below');
                        tooltip.classList.remove('tooltip-above');
                    } else {
                        // Lower half - show tooltip above
                        tooltip.style.top = (rect.top - tooltipHeight - 10) + 'px';
                        tooltip.classList.remove('tooltip-below');
                        tooltip.classList.add('tooltip-above');
                    }

                    // Ensure tooltip stays within screen bounds
                    const finalLeft = Math.max(10, Math.min(leftPos, window.innerWidth - tooltipWidth - 10));
                    tooltip.style.left = finalLeft + 'px';
                    tooltip.style.transform = 'none';
                } else {
                    // Desktop: Use original positioning logic
                    if (iconCenterY < screenMiddle) {
                        // Upper half - show tooltip below
                        tooltip.classList.add('tooltip-below');
                        tooltip.classList.remove('tooltip-above');
                    } else {
                        // Lower half - show tooltip above
                        tooltip.classList.remove('tooltip-below');
                        tooltip.classList.add('tooltip-above');
                    }
                }
            };

            const hideTooltip = function(e) {
                // Use the global hideActiveTooltip function
                hideActiveTooltip();
            };

            // Add event listeners for both desktop and mobile
            container.addEventListener('mouseenter', showTooltip);
            container.addEventListener('mouseleave', hideTooltip);

            // Mobile touch events
            container.addEventListener('touchstart', showTooltip);
            container.addEventListener('touchend', hideTooltip);
        });
    }

    // Initialize tooltips when modal is opened
    openTierModalBtn?.addEventListener('click', () => {
        setTimeout(() => {
            initializeTierTooltips();
        }, 100);
    });

    // --- Initial Load ---
    applyAllFilters(); // Apply all filters on initial page load
    initializeTierTooltips(); // Initialize tooltips if modal is already open
    // Set initial sort state if any header has 'active-sort' (e.g. from server-side preference or previous state)
    // For now, we assume no default sort beyond what the server provides initially.
    // If a default client-side sort is needed, it can be triggered here.
});