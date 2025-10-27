document.addEventListener('DOMContentLoaded', () => {
    // Version/Tier selection logic for detail page filters
    const versionFilterSelect = document.getElementById('sel-version-filter'); // Use updated ID
    const tierFilterSelect = document.getElementById('sel-tier-filter'); // Use updated ID

    // --- Initialize select boxes from URL parameters ---
    function initializeFiltersFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);

        // Sync tier select
        if (tierFilterSelect) {
            const tierParam = urlParams.get('min_tier');
            if (tierParam && tierFilterSelect.value !== tierParam) {
                tierFilterSelect.value = tierParam;
            }
        }

        // Sync version select
        if (versionFilterSelect) {
            const versionParam = urlParams.get('version');
            if (versionParam && versionFilterSelect.value !== versionParam) {
                versionFilterSelect.value = versionParam;
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

    function updateDetailUrl() {
        const selectedVersion = versionFilterSelect.value;
        const selectedTier = tierFilterSelect.value;

        // Get the current path (e.g., /detail/CharacterName-WeaponType)
        const currentPath = window.location.pathname;

        // Construct the new URL with updated query parameters
        window.location.href = `${currentPath}?min_tier=${selectedTier}&version=${selectedVersion}`;
    }

    // Attach event listeners to the correct filter selects
    if (versionFilterSelect) {
        versionFilterSelect.addEventListener('change', updateDetailUrl);
    }
    if (tierFilterSelect) {
        tierFilterSelect.addEventListener('change', updateDetailUrl);
    }

    // Table sorting logic
    const gradeOrder = {
        "Common": 0,
        "Uncommon": 1,
        "Rare": 2,
        "Epic": 3,
        "Legend": 4, // Combined Legend/Legendary
        "Mythic": 5, // Combined Mythic/Mythical
        "Legendary": 4,
        "Mythical": 5
    };

    const allTables = document.querySelectorAll('table.sortable-table');

    allTables.forEach(table => {
        const headers = table.querySelectorAll('th.sortable');
        let currentSort = { index: null, dir: 'asc' }; // Store sort state per table

        headers.forEach((header, index) => { // Use index directly if data-sort-index is not reliable
            // Fallback if data-sort-index is missing or incorrect
            const sortIndex = header.dataset.sortIndex ? parseInt(header.dataset.sortIndex) : index;
            const sortType = header.dataset.sortType || 'text'; // Default to text sort

            header.addEventListener('click', () => {
                const tbody = table.querySelector('tbody');
                if (!tbody) return; // Exit if no tbody
                const rows = Array.from(tbody.querySelectorAll('tr'));

                // Determine sort direction
                if (currentSort.index === sortIndex) {
                    currentSort.dir = currentSort.dir === 'asc' ? 'desc' : 'asc';
                } else {
                    currentSort.index = sortIndex;
                    currentSort.dir = 'asc'; // Default to ascending for new column
                }

                // Update header classes for visual feedback
                headers.forEach(th => th.classList.remove('asc', 'desc'));
                header.classList.add(currentSort.dir);

                // Sort rows
                const sortedRows = rows.sort((a, b) => {
                    const aVal = getCellValue(a, sortIndex, sortType);
                    const bVal = getCellValue(b, sortIndex, sortType);

                    // Comparison logic
                    if (sortType === 'number' || sortType === 'grade') {
                        return currentSort.dir === 'asc' ? aVal - bVal : bVal - aVal;
                    } else { // Default to string comparison
                        return currentSort.dir === 'asc'
                            ? String(aVal).localeCompare(String(bVal))
                            : String(bVal).localeCompare(String(aVal));
                    }
                });

                // Re-append sorted rows
                tbody.innerHTML = ''; // Clear existing rows
                sortedRows.forEach(row => tbody.appendChild(row));
            });
        });
    });

    function getCellValue(row, index, type) {
        const cell = row.cells[index];
        if (!cell) return type === 'number' || type === 'grade' ? 0 : ''; // Default value based on type

        // Get text, handle potential multiple lines (e.g., from sub-stat)
        const textContent = cell.querySelector('div:not(.sub-stat)')?.innerText || cell.innerText;
        const text = textContent.trim().split('\n')[0]; // Use first line if multiple exist

        if (type === 'grade') {
            return gradeOrder[text] ?? 999; // Use lookup, default to high value if not found
        }

        if (type === 'number') {
            const num = parseFloat(text.replace(/[,%]/g, ''));
            return isNaN(num) ? 0 : num; // Default to 0 if parsing fails
        }

        return text; // Return text for string comparison
    }


    // Tab switching logic
    window.openTab = function(evt, tabName) { // Make function global or attach to window
        var i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("tab-content");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
            tabcontent[i].classList.remove("active");
        }
        tablinks = document.getElementsByClassName("tab-link");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" active", "");
        }
        const currentTabContent = document.getElementById(tabName);
        if (currentTabContent) {
            currentTabContent.style.display = "block";
            currentTabContent.classList.add("active");
        }
        if (evt && evt.currentTarget) {
             evt.currentTarget.className += " active";
        }
    }

    // Ensure the first tab content is displayed on initial load
    const activeTabLink = document.querySelector('.tab-link.active');
    const firstTabLink = document.querySelector('.tab-link'); // Fallback

    if (activeTabLink) {
        // Find the target tab content ID from the active link's onclick attribute or a data attribute
        const onclickAttr = activeTabLink.getAttribute('onclick');
        const tabIdMatch = onclickAttr ? onclickAttr.match(/openTab\(event, '(.+?)'\)/) : null;
        if (tabIdMatch && tabIdMatch[1]) {
            const activeTabContent = document.getElementById(tabIdMatch[1]);
            if (activeTabContent) {
                activeTabContent.style.display = 'block';
                activeTabContent.classList.add('active'); // Ensure class is set
            }
        } else {
             // If parsing onclick fails, try activating the first tab as a fallback
             if(firstTabLink) firstTabLink.click();
        }
    } else if (firstTabLink) {
        // If no tab is marked active in HTML, activate the first one.
        firstTabLink.click();
    }

    // --- Tactical Skill "Show More" Logic ---
    const showMoreTacticalSkillsButton = document.getElementById('show-more-tactical-skills');
    const tacticalSkillRows = document.querySelectorAll('#tactical-skill-tbody tr.tactical-skill-row');

    // Initially hide the button, we'll show it only if needed
    if (showMoreTacticalSkillsButton) {
        showMoreTacticalSkillsButton.style.display = 'none';
    }

    if (showMoreTacticalSkillsButton && tacticalSkillRows.length > 5) {
        // Show the button only if there are more than 5 rows
        showMoreTacticalSkillsButton.style.display = 'block';
        showMoreTacticalSkillsButton.textContent = '더보기';

        showMoreTacticalSkillsButton.addEventListener('click', () => {
            tacticalSkillRows.forEach((row, index) => {
                // Show rows starting from the 6th (index 5)
                if (index >= 5) {
                    row.style.display = ''; // Remove inline style to show
                }
            });
            showMoreTacticalSkillsButton.style.display = 'none'; // Hide the button after clicking
        });
    }

    // --- Grade filter logic for equipment tabs ---
    document.querySelectorAll('.grade-filter-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const tabKey = this.dataset.tabKey;
            const tabContent = document.getElementById(`${tabKey}-stats`);
            if (!tabContent) return;

            const tableBody = tabContent.querySelector('tbody');
            if (!tableBody) return;

            // Get checked grades for this specific tab
            const checkedGrades = Array.from(tabContent.querySelectorAll('.grade-filter-checkbox:checked'))
                                      .map(cb => cb.value);

            const rows = tableBody.querySelectorAll('tr');
            rows.forEach(row => {
                const grade = row.dataset.grade;
                // Show row if its grade is in the checked list or if no grade is set (handle potential edge cases)
                if (grade && checkedGrades.includes(grade)) {
                    row.style.display = ''; // Show row
                } else {
                    row.style.display = 'none'; // Hide row
                }
            });
        });
    });

    // Initial filter application on page load for each equipment tab
    document.querySelectorAll('.tab-content').forEach(tabContent => {
        const tableBody = tabContent.querySelector('tbody');
        if (!tableBody) return;

        const checkedGrades = Array.from(tabContent.querySelectorAll('.grade-filter-checkbox:checked'))
                                  .map(cb => cb.value);

        const rows = tableBody.querySelectorAll('tr');
        rows.forEach(row => {
            const grade = row.dataset.grade;
            if (grade && checkedGrades.includes(grade)) {
                row.style.display = '';
            } else if (grade) { // Hide only if grade exists but is not checked
                row.style.display = 'none';
            }
        });
    });

    // --- Trait Category Filter Logic ---
    const traitCategoryCheckboxes = document.querySelectorAll('.trait-category-filter-checkbox');
    const traitIsMainCheckboxes = document.querySelectorAll('.trait-is-main-filter-checkbox');
    const traitTableBody = document.getElementById('trait-tbody');
    const traitRows = traitTableBody ? Array.from(traitTableBody.querySelectorAll('tr.trait-row')) : [];
    const showMoreTraitsButtonJs = document.getElementById('show-more-traits');
    let traitShowMoreActivated = false; // State variable for "Show More"

    function filterTraits() {
        if (!traitTableBody) return; // Check elements exist

        const checkedIsMains = Array.from(document.querySelectorAll('.trait-is-main-filter-checkbox:checked'))
            .map(cb => cb.value);
        const checkedCategories = Array.from(document.querySelectorAll('.trait-category-filter-checkbox:checked'))
            .map(cb => cb.value);

        let visibleCount = 0;
        let hiddenMatchingCount = 0;
        const showLimit = traitShowMoreActivated ? 999 : 10; // Show 10 initially, then all

        traitRows.forEach((row, index) => {
            const is_main = row.dataset.is_main;
            const matchesIsMain = is_main && checkedIsMains.includes(is_main);
            const category = row.dataset.category;
            const matchesCategory = category && checkedCategories.includes(category);

            if (matchesIsMain && matchesCategory) {
                if (visibleCount < showLimit) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                    hiddenMatchingCount++;
                }
            } else {
                // Hide if category doesn't match
                row.style.display = 'none';
            }
        });

        // Update "Show More" button
        if (hiddenMatchingCount > 0 && !traitShowMoreActivated && showMoreTraitsButtonJs) {
            showMoreTraitsButtonJs.style.display = 'block';
            showMoreTraitsButtonJs.textContent = '더보기';
        } else if (showMoreTraitsButtonJs) {
            showMoreTraitsButtonJs.style.display = 'none';
        }
    }

    // Add event listeners to trait category checkboxes
    traitCategoryCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', filterTraits);
    });
    // Add event listeners to trait category checkboxes
    traitIsMainCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', filterTraits);
    });

    // Define the "Show More" handler function
    const showMoreTraitsHandler = () => {
        if (!showMoreTraitsButtonJs) return;
        // Set the state variable to true and re-run the filter function.
        // The filter function will now show all matching rows and hide the button.
        traitShowMoreActivated = true;
        filterTraits();
    };

    // Add the single, correct event listener for "Show More"
    if (showMoreTraitsButtonJs) {
        showMoreTraitsButtonJs.addEventListener('click', showMoreTraitsHandler);
    }

    // Initial trait filter application on page load
    filterTraits();

    // --- Tier Info Toggle Logic ---
    const toggleTierInfoButton = document.getElementById('toggle-tier-info');
    const tierInfoContainer = document.getElementById('tier-info-container');

    if (toggleTierInfoButton && tierInfoContainer) {
        toggleTierInfoButton.addEventListener('click', function() {
            if (tierInfoContainer.style.display === 'none' || tierInfoContainer.style.display === '') {
                tierInfoContainer.style.display = 'block';
                this.innerHTML = '▲ 접기';
            } else {
                tierInfoContainer.style.display = 'none';
                this.innerHTML = '▼ 펼치기';
            }
        });
    }

}); // End DOMContentLoaded
