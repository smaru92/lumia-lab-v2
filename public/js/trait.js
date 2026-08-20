document.addEventListener('DOMContentLoaded', () => {
    // Element references
    const inputMinCount = document.getElementById('input-min-count');
    const gameTable = document.getElementById("gameTable");
    const tableBody = gameTable?.querySelector("tbody");

    // Trait page specific filters
    const selTraitType = document.getElementById('sel-trait-type');
    const selTraitCategory = document.getElementById('sel-trait-category');

    // Modal elements
    const tierModal = document.getElementById('tierModal');
    const openTierModalBtn = document.getElementById('openTierModal');
    const closeButton = document.querySelector('.close-button');

    // Custom dropdown elements
    const versionDropdown = document.getElementById('version-dropdown');
    const tierDropdown = document.getElementById('tier-dropdown');

    // --- Custom Dropdown Logic ---
    function initCustomDropdowns() {
        const dropdowns = document.querySelectorAll('.custom-dropdown');

        dropdowns.forEach(dropdown => {
            const selected = dropdown.querySelector('.dropdown-selected');
            const options = dropdown.querySelector('.dropdown-options');
            const optionItems = dropdown.querySelectorAll('.dropdown-option');

            if (!selected || !options) return;

            // Toggle dropdown on click
            selected.addEventListener('click', (e) => {
                e.stopPropagation();

                // Close other dropdowns
                dropdowns.forEach(d => {
                    if (d !== dropdown) {
                        d.classList.remove('open');
                    }
                });

                dropdown.classList.toggle('open');
            });

            // Handle option selection
            optionItems.forEach(option => {
                option.addEventListener('click', (e) => {
                    e.stopPropagation();

                    const value = option.dataset.value;
                    const html = option.innerHTML;

                    // Update selected display
                    selected.innerHTML = html;
                    selected.dataset.value = value;

                    // Update selected state
                    optionItems.forEach(opt => opt.classList.remove('selected'));
                    option.classList.add('selected');

                    // Close dropdown
                    dropdown.classList.remove('open');

                    // Trigger URL update
                    updateUrlFromDropdowns();
                });
            });
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', () => {
            dropdowns.forEach(dropdown => {
                dropdown.classList.remove('open');
            });
        });
    }

    function updateUrlFromDropdowns() {
        const versionSelected = versionDropdown?.querySelector('.dropdown-selected');
        const tierSelected = tierDropdown?.querySelector('.dropdown-selected');

        if (!versionSelected && !tierSelected) return;

        const currentUrl = new URL(window.location.href);

        if (versionSelected) {
            currentUrl.searchParams.set('version', versionSelected.dataset.value);
        }
        if (tierSelected) {
            currentUrl.searchParams.set('min_tier', tierSelected.dataset.value);
        }

        if (window.location.href !== currentUrl.href) {
            window.location.href = currentUrl.href;
        }
    }

    // Initialize custom dropdowns
    initCustomDropdowns();

    // --- Filter Logic ---
    function applyAllFilters() {
        if (!tableBody && !tierModal) return;

        const minCount = parseInt(inputMinCount?.value) || 0;
        const selectedType = selTraitType?.value || 'All';
        const selectedCategory = selTraitCategory?.value || 'All';

        // Filter main table rows
        if (tableBody) {
            tableBody.querySelectorAll("tr").forEach(row => {
                let showRow = true;

                // Minimum usage count filter (column 3)
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

                // Trait type filter
                if (selTraitType && selectedType !== 'All') {
                    if (row.dataset.traitType !== selectedType) {
                        showRow = false;
                    }
                }

                // Trait category filter (대소문자 무시)
                if (selTraitCategory && selectedCategory !== 'All') {
                    const rowCategory = (row.dataset.traitCategory || '').toLowerCase();
                    const filterCategory = selectedCategory.toLowerCase();
                    if (rowCategory !== filterCategory) {
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

                // Filter by game count
                if (inputMinCount) {
                    const gameCountValue = parseInt(iconContainer.dataset.gameCount) || 0;
                    if (gameCountValue < minCount) {
                        showIcon = false;
                    }
                }

                // Trait type filter
                if (selTraitType && selectedType !== 'All') {
                    if (iconContainer.dataset.traitType !== selectedType) {
                        showIcon = false;
                    }
                }

                // Trait category filter (대소문자 무시)
                if (selTraitCategory && selectedCategory !== 'All') {
                    const iconCategory = (iconContainer.dataset.traitCategory || '').toLowerCase();
                    const filterCategory = selectedCategory.toLowerCase();
                    if (iconCategory !== filterCategory) {
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
                        row.style.display = "none";
                    } else {
                        row.style.display = "";
                        tierContent.innerHTML = "&nbsp;";
                        row.classList.add("empty-tier-row");
                        tierContent.classList.add("empty-tier-content");
                    }
                } else {
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
    if (inputMinCount) {
        inputMinCount.addEventListener('input', () => {
            localStorage.setItem('traitMinCountFilter', inputMinCount.value);
            applyAllFilters();
        });
        // Load saved min count
        const savedMinCount = localStorage.getItem('traitMinCountFilter');
        if (savedMinCount) {
            inputMinCount.value = savedMinCount;
        }
    }
    if (selTraitType) {
        selTraitType.addEventListener('change', applyAllFilters);
    }
    if (selTraitCategory) {
        selTraitCategory.addEventListener('change', applyAllFilters);
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
                isAscending = !header.classList.contains("asc");
            } else {
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
        const rowsToSort = Array.from(tableBody.querySelectorAll("tr:not([style*='display: none'])"));

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
                // 쉼표와 퍼센트 기호를 제거하여 숫자로 파싱
                const numA = parseFloat(cellA.replace(/,/g, '').replace(/%/g, ''));
                const numB = parseFloat(cellB.replace(/,/g, '').replace(/%/g, ''));

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
        hiddenRows.forEach(row => tableBody.appendChild(row));

        renumberRankColumn();
    }

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

    // Bottom close button handler
    document.addEventListener('click', (event) => {
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

    // --- Tier Table Image Download ---
    function loadHtml2Canvas() {
        return new Promise((resolve, reject) => {
            if (window.html2canvas) return resolve(window.html2canvas);
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
            script.onload = () => resolve(window.html2canvas);
            script.onerror = () => reject(new Error('html2canvas load failed'));
            document.head.appendChild(script);
        });
    }

    // Character icons are served at 80px wide for the web, which is too small for
    // the 2x export (icons render ~70px = 140 device px) and looks blurry.
    // The pre-resize originals (122x160) are kept under /icon/origin, so use those
    // for the exported image only. Falls back to the served icon if origin is missing.
    const hiResIconCache = new Map();
    function hiResIconSrc(src) {
        return src.replace(/\/Character\/icon\/([^/?]+)(\?.*)?$/i, '/Character/icon/origin/$1$2');
    }
    function resolveHiResIcon(src) {
        const hiRes = hiResIconSrc(src);
        if (hiRes === src) return Promise.resolve(src);
        if (!hiResIconCache.has(hiRes)) {
            hiResIconCache.set(hiRes, new Promise((resolve) => {
                const probe = new Image();
                probe.onload = () => resolve(probe.naturalWidth ? hiRes : src);
                probe.onerror = () => resolve(src);
                probe.src = hiRes;
            }));
        }
        return hiResIconCache.get(hiRes);
    }

    document.addEventListener('click', async (event) => {
        const btn = event.target.closest('.tier-download-btn');
        if (!btn) return;

        const captureArea = btn.closest('.modal-content')?.querySelector('.tier-capture-area');
        if (!captureArea) return;

        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = '이미지 생성 중...';

        let clone = null;
        try {
            const html2canvas = await loadHtml2Canvas();

            // Always export using the PC layout (10 icons per row) regardless of the
            // current device, at a fixed PC content width.
            const PC_CONTENT_WIDTH = 860;
            clone = captureArea.cloneNode(true);
            clone.style.position = 'fixed';
            clone.style.top = '0';
            clone.style.left = '-10000px';
            clone.style.boxSizing = 'content-box';
            clone.style.width = PC_CONTENT_WIDTH + 'px';
            clone.style.margin = '0';
            clone.style.padding = '20px';
            clone.style.background = '#ffffff';
            document.body.appendChild(clone);

            // Force the PC (10-per-row) layout inline. Inline !important beats the
            // mobile media-query rules so the exported image looks like the PC modal.
            clone.querySelectorAll('.tier-table td:nth-child(2)').forEach((cell) => {
                cell.style.setProperty('display', 'flex', 'important');
                cell.style.setProperty('flex-wrap', 'wrap', 'important');
                cell.style.setProperty('gap', '8px', 'important');
            });
            clone.querySelectorAll('.tier-character-icon-container').forEach((c) => {
                c.style.setProperty('flex', '0 0 calc((100% - 9 * 8px) / 10)', 'important');
                c.style.setProperty('max-width', 'calc((100% - 9 * 8px) / 10)', 'important');
                c.style.setProperty('width', 'auto', 'important');
            });

            // Ensure all (lazy-loaded) images are fully loaded before capture
            const imgs = Array.from(clone.querySelectorAll('img'));
            await Promise.all(imgs.map((img) => {
                if (img.complete && img.naturalWidth) return Promise.resolve();
                img.loading = 'eager';
                return new Promise((res) => { img.onload = img.onerror = res; });
            }));

            // html2canvas does not honor object-fit:cover, so it would stretch the
            // portraits. Convert those icons to background-image divs so the export
            // keeps the exact cropped ratio the user sees on the web, swapping in the
            // higher-resolution originals so the 2x capture stays sharp.
            const iconImgs = Array.from(clone.querySelectorAll('img.tier-character-icon'));
            const iconSrcs = await Promise.all(iconImgs.map((img) => resolveHiResIcon(img.src)));
            iconImgs.forEach((img, index) => {
                const cs = window.getComputedStyle(img);
                const div = document.createElement('div');
                div.className = img.className;
                div.style.width = cs.width;
                div.style.height = cs.height;
                div.style.display = 'inline-block';
                div.style.verticalAlign = cs.verticalAlign;
                div.style.borderRadius = cs.borderRadius;
                div.style.border = cs.border;
                div.style.boxSizing = cs.boxSizing;
                div.style.backgroundImage = 'url("' + iconSrcs[index] + '")';
                div.style.backgroundSize = 'cover';
                div.style.backgroundPosition = 'center';
                div.style.backgroundRepeat = 'no-repeat';
                img.replaceWith(div);
            });

            const canvas = await html2canvas(clone, {
                backgroundColor: '#ffffff',
                scale: 2,
                useCORS: true,
                logging: false,
                width: clone.offsetWidth,
                height: clone.offsetHeight
            });

            const link = document.createElement('a');
            link.download = 'lumia-tier-list.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        } catch (e) {
            console.error('Tier image download failed:', e);
            alert('이미지 다운로드에 실패했습니다. 잠시 후 다시 시도해주세요.');
        } finally {
            if (clone && clone.parentNode) clone.parentNode.removeChild(clone);
            btn.disabled = false;
            btn.textContent = originalText;
        }
    });

    // --- Initial Load ---
    applyAllFilters();
});
