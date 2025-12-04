document.addEventListener('DOMContentLoaded', () => {
    // Element references
    const selMinTier = document.getElementById('sel-min-tier'); // Version filter in main.blade.php & equipment.blade.php
    const selVersion = document.getElementById('sel-version'); // Tier filter in main.blade.php & equipment.blade.php
    const inputMinCount = document.getElementById('input-min-count');
    const gameTable = document.getElementById("gameTable");
    const tableBody = gameTable?.querySelector("tbody");

    // Equipment page specific filters
    const selItemGrade = document.getElementById('sel-item-grade');
    const selItemType2 = document.getElementById('sel-item-type2');

    // Modal elements
    const tierModal = document.getElementById('tierModal');
    const openTierModalBtn = document.getElementById('openTierModal');
    const closeButton = document.querySelector('.close-button');

    // --- Initialize select boxes from URL parameters ---
    function initializeFiltersFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);

        // Sync version select (sel-min-tier is actually the version selector)
        if (selMinTier) {
            const versionParam = urlParams.get('version');
            if (versionParam && selMinTier.value !== versionParam) {
                selMinTier.value = versionParam;
            }
        }

        // Sync min_tier select (sel-version is actually the min_tier selector)
        if (selVersion) {
            const minTierParam = urlParams.get('min_tier');
            if (minTierParam && selVersion.value !== minTierParam) {
                selVersion.value = minTierParam;
            }
        }
    }

    // Initialize on page load
    initializeFiltersFromUrl();

    // Re-initialize on browser back/forward
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) {
            // Page was loaded from cache (bfcache)
            initializeFiltersFromUrl();
        }
    });

    // Also handle popstate event for browser back/forward
    window.addEventListener('popstate', function() {
        initializeFiltersFromUrl();
    });

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

        const minCount = parseInt(inputMinCount?.value) || 0;
        const selectedGrade = selItemGrade?.value || 'All';
        const selectedType2 = selItemType2?.value || 'All';

        // Filter main table rows
        if (tableBody) {
            tableBody.querySelectorAll("tr").forEach(row => {
                let showRow = true;

                if (inputMinCount) {
                    const countCell = row.cells[3];
                    if (countCell) {
                        const countText = countCell.innerText.trim().replace(/,/g, '');
                        const countValue = parseInt(countText) || 0;
                        if (countValue < minCount) {
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

                const gameCountValue = parseInt(iconContainer.dataset.gameCount) || 0;
                if (gameCountValue < minCount) {
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

            // Handle empty tier rows dynamically
            const tierRows = tierModal.querySelectorAll(".tier-table tr");
            tierRows.forEach(row => {
                const tierContent = row.querySelector("td:nth-child(2)");
                if (!tierContent) return;

                // Check if this tier row has any visible icons
                const visibleIcons = tierContent.querySelectorAll(".tier-character-icon-container:not([style*='display: none'])");
                const hasVisibleIcons = visibleIcons.length > 0;

                // Get tier name from the tier badge
                const tierBadge = row.querySelector(".tier-badge");
                const tierName = tierBadge ? tierBadge.textContent.trim() : '';
                const isSpecialTier = ['OP', 'RIP'].includes(tierName);

                if (!hasVisibleIcons) {
                    if (isSpecialTier) {
                        // Hide OP/RIP tiers when they have no visible items
                        row.style.display = "none";
                    } else {
                        // Show regular tiers as empty with proper height
                        row.style.display = "";
                        tierContent.innerHTML = "&nbsp;";
                        row.classList.add("empty-tier-row");
                        tierContent.classList.add("empty-tier-content");
                    }
                } else {
                    // Show tier with items
                    row.style.display = "";
                    row.classList.remove("empty-tier-row");
                    tierContent.classList.remove("empty-tier-content");
                }
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
    if (inputMinCount) {
        inputMinCount.addEventListener('input', () => {
            localStorage.setItem('minCountFilter', inputMinCount.value); // Save min count
            applyAllFilters();
        });
        // Load saved min count
        const savedMinCount = localStorage.getItem('minCountFilter');
        if (savedMinCount) {
            inputMinCount.value = savedMinCount;
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
            const columnIndex = header.cellIndex;
            // 이름(1번), 티어(2번) 컬럼은 오름차순 우선, 나머지는 내림차순 우선
            const isNameOrTierColumn = columnIndex === 1 || columnIndex === 2;

            let isAscending;
            if (header.classList.contains("active-sort")) {
                // 이미 정렬된 컬럼을 다시 클릭하면 방향 전환
                isAscending = !header.classList.contains("asc");
            } else {
                // 새로운 컬럼 클릭: 이름/티어는 오름차순, 나머지는 내림차순
                isAscending = isNameOrTierColumn;
            }

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

    // Bottom close button handler - use event delegation
    document.addEventListener('click', (event) => {
        // Check if clicked element has close-modal class
        if (event.target.classList.contains('close-modal')) {
            if (tierModal) {
                tierModal.style.display = 'none';
            }
        }

        // Close modal when clicking outside
        if (event.target === tierModal) {
            tierModal.style.display = 'none';
        }
    });

    // --- Initial Load ---
    applyAllFilters(); // Apply all filters on initial page load
    // Set initial sort state if any header has 'active-sort' (e.g. from server-side preference or previous state)
    // For now, we assume no default sort beyond what the server provides initially.
    // If a default client-side sort is needed, it can be triggered here.
});