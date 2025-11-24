/**
 * Detail Page Lazy Loading
 * 각 섹션을 스크롤 시 동적으로 로드
 */

document.addEventListener('DOMContentLoaded', function() {
    // 현재 URL 파라미터 가져오기
    const urlParams = new URLSearchParams(window.location.search);
    const pathParts = window.location.pathname.split('/');
    const types = pathParts[pathParts.length - 1]; // e.g., "Aya-Bow"

    const version = urlParams.get('version') || document.getElementById('sel-version-filter')?.value || '';
    const minTier = urlParams.get('min_tier') || document.getElementById('sel-tier-filter')?.value || 'Diamond';

    // 로드 상태 추적
    const loadedSections = {
        tiers: false,
        ranks: false,
        tacticalSkills: false,
        equipment: false,
        traits: false
    };

    /**
     * Intersection Observer 설정
     * 섹션이 뷰포트에 들어오면 데이터 로드
     */
    const observerOptions = {
        root: null,
        rootMargin: '100px', // 섹션이 뷰포트 100px 전에 로드 시작
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const section = entry.target;
                const sectionType = section.dataset.lazySection;

                if (!loadedSections[sectionType]) {
                    loadSection(sectionType, section);
                    loadedSections[sectionType] = true;
                }
            }
        });
    }, observerOptions);

    // 모든 lazy 섹션 관찰 시작
    const sections = document.querySelectorAll('[data-lazy-section]');
    sections.forEach(section => observer.observe(section));

    /**
     * 섹션 데이터 로드
     */
    async function loadSection(sectionType, sectionElement) {
        showLoading(sectionElement);

        try {
            let endpoint = '';
            switch(sectionType) {
                case 'tiers':
                    endpoint = `/api/detail/${types}/tiers?version=${version}&min_tier=${minTier}`;
                    break;
                case 'ranks':
                    endpoint = `/api/detail/${types}/ranks?version=${version}&min_tier=${minTier}`;
                    break;
                case 'tacticalSkills':
                    endpoint = `/api/detail/${types}/tactical-skills?version=${version}&min_tier=${minTier}`;
                    break;
                case 'equipment':
                    endpoint = `/api/detail/${types}/equipment?version=${version}&min_tier=${minTier}`;
                    break;
                case 'traits':
                    endpoint = `/api/detail/${types}/traits?version=${version}&min_tier=${minTier}`;
                    break;
            }

            const response = await fetch(endpoint);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log(`${sectionType} data:`, data); // 디버깅용
            renderSection(sectionType, data, sectionElement);
        } catch (error) {
            console.error(`Error loading ${sectionType}:`, error);
            showError(sectionElement, `데이터를 불러오는데 실패했습니다: ${error.message}`);
        }
    }

    /**
     * 로딩 스피너 표시
     */
    function showLoading(element) {
        element.innerHTML = `
            <div class="loading-skeleton">
                <div class="skeleton-table">
                    <div class="skeleton-row"></div>
                    <div class="skeleton-row"></div>
                    <div class="skeleton-row"></div>
                    <div class="skeleton-row"></div>
                </div>
            </div>
        `;
    }

    /**
     * 에러 메시지 표시
     */
    function showError(element, message) {
        element.innerHTML = `
            <div class="error-message" style="padding: 20px; text-align: center; color: #d32f2f;">
                <p>${message}</p>
            </div>
        `;
    }

    /**
     * 섹션별 데이터 렌더링
     */
    function renderSection(sectionType, data, element) {
        switch(sectionType) {
            case 'tiers':
                renderTiersSection(data, element);
                break;
            case 'ranks':
                renderRanksSection(data, element);
                break;
            case 'tacticalSkills':
                renderTacticalSkillsSection(data, element);
                break;
            case 'equipment':
                renderEquipmentSection(data, element);
                break;
            case 'traits':
                renderTraitsSection(data, element);
                break;
        }
    }

    /**
     * 전체 티어정보 렌더링
     */
    function renderTiersSection(data, element) {
        const byAll = data.byAll || {};

        let html = '<div class="table-wrapper"><table id="tierInfoTable">';
        html += `
            <thead>
                <tr>
                    <th>최소티어</th>
                    <th>티어</th>
                    <th>픽률</th>
                    <th>평균획득점수<span class="info-icon" data-tooltip="입장료를 차감하지 않고 게임 내에서 획득 점수를 나타냅니다.">ⓘ</span></th>
                    <th>승률</th>
                    <th>TOP2</th>
                    <th>TOP4</th>
                    <th>막금구승률</th>
                    <th>평균 TK</th>
                    <th>이득확률</th>
                    <th>이득평균점수</th>
                    <th>손실확률</th>
                    <th>손실평균점수</th>
                </tr>
            </thead>
            <tbody>
        `;

        for (const [tierKey, item] of Object.entries(byAll)) {
            if (!item) continue;

            const tierClass = `tier-${(item.meta_tier || '').toLowerCase().replace(' ', '-')}`;

            // 디버깅: 첫 번째 아이템 출력
            if (tierKey === Object.keys(byAll)[0]) {
                console.log('First tier item:', item);
            }

            html += `
                <tr style="cursor: pointer;">
                    <td>${item.tier_name || ''}</td>
                    <td data-score="${item.meta_score || 0}">
                        <span class="tier-badge ${tierClass}">${item.meta_tier || ''}</span>
                        <div class="sub-stat">${formatNumber(item.meta_score_rank || 0)} / ${formatNumber(item.rank_count || 0)}</div>
                    </td>
                    <td class="number">
                        <div>${formatPercent(item.game_count_percent || 0)}%</div>
                        <div class="sub-stat">${formatNumber(item.game_count_rank || 0)} / ${formatNumber(item.rank_count || 0)}</div>
                    </td>
                    <td class="number">
                        ${formatNumber(item.avg_mmr_gain || 0, 1)}
                        <div class="sub-stat">${formatNumber(item.avg_mmr_gain_rank || 0)} / ${formatNumber(item.rank_count || 0)}</div>
                    </td>
                    <td class="number">
                        <div>${formatPercent(item.top1_count_percent || 0)}%</div>
                        <div class="sub-stat">${formatNumber(item.top1_count_percent_rank || 0)} / ${formatNumber(item.rank_count || 0)}</div>
                    </td>
                    <td class="number">
                        <div>${formatPercent(item.top2_count_percent || 0)}%</div>
                        <div class="sub-stat">${formatNumber(item.top2_count_percent_rank || 0)} / ${formatNumber(item.rank_count || 0)}</div>
                    </td>
                    <td class="number">
                        <div>${formatPercent(item.top4_count_percent || 0)}%</div>
                        <div class="sub-stat">${formatNumber(item.top4_count_percent_rank || 0)} / ${formatNumber(item.rank_count || 0)}</div>
                    </td>
                    <td class="number">
                        <div>${formatPercent(item.endgame_win_percent || 0)}%</div>
                        <div class="sub-stat">${formatNumber(item.endgame_win_percent_rank || 0)} / ${formatNumber(item.rank_count || 0)}</div>
                    </td>
                    <td class="number">
                        ${formatNumber(item.avg_team_kill_score || 0, 2)}
                        <div class="sub-stat">${formatNumber(item.avg_team_kill_score_rank || 0)} / ${formatNumber(item.rank_count || 0)}</div>
                    </td>
                    <td class="number">
                        <div>${formatPercent(item.positive_game_count_percent || 0)}%</div>
                        <div class="sub-stat">${formatNumber(item.positive_game_count_percent_rank || 0)} / ${formatNumber(item.rank_count || 0)}</div>
                    </td>
                    <td class="number">
                        ${formatNumber(item.positive_avg_mmr_gain || 0, 1)}
                        <div class="sub-stat">${formatNumber(item.positive_avg_mmr_gain_rank || 0)} / ${formatNumber(item.rank_count || 0)}</div>
                    </td>
                    <td class="number">
                        <div>${formatPercent(item.negative_game_count_percent || 0)}%</div>
                        <div class="sub-stat">${formatNumber(item.negative_game_count_percent_rank || 0)} / ${formatNumber(item.rank_count || 0)}</div>
                    </td>
                    <td class="number">
                        ${formatNumber(item.negative_avg_mmr_gain || 0, 1)}
                        <div class="sub-stat">${formatNumber(item.negative_avg_mmr_gain_rank || 0)} / ${formatNumber(item.rank_count || 0)}</div>
                    </td>
                </tr>
            `;
        }

        html += '</tbody></table></div>';
        element.innerHTML = html;
    }

    /**
     * 순위 통계 렌더링
     */
    function renderRanksSection(data, element) {
        const byRank = data.byRank || [];

        let html = '<div class="table-wrapper"><table id="rankStatsTable">';
        html += `
            <thead>
                <tr>
                    <th>순위</th>
                    <th>픽률</th>
                    <th>평균획득점수<span class="info-icon" data-tooltip="입장료를 차감하지 않고 게임 내에서 획득 점수를 나타냅니다.">ⓘ</span></th>
                    <th>평균 TK</th>
                    <th>이득확률</th>
                    <th>이득평균점수</th>
                    <th>손실확률</th>
                    <th>손실평균점수</th>
                </tr>
            </thead>
            <tbody>
        `;

        byRank.forEach(item => {
            html += `
                <tr>
                    <td>${item.game_rank || ''}</td>
                    <td class="number">
                        <div>${formatPercent(item.game_rank_count_percent)}%</div>
                        <div class="sub-stat">${formatNumber(item.game_rank_count)}</div>
                    </td>
                    <td class="number">${formatNumber(item.avg_mmr_gain, 2)}</td>
                    <td class="number">${formatNumber(item.avg_team_kill_score, 2)}</td>
                    <td class="number">
                        <div>${formatPercent(item.positive_count_percent)}%</div>
                        <div class="sub-stat">${formatNumber(item.positive_count)}</div>
                    </td>
                    <td class="number">${formatNumber(item.positive_avg_mmr_gain, 1)}</td>
                    <td class="number">
                        <div>${formatPercent(item.negative_count_percent)}%</div>
                        <div class="sub-stat">${formatNumber(item.negative_count)}</div>
                    </td>
                    <td class="number">${formatNumber(item.negative_avg_mmr_gain, 1)}</td>
                </tr>
            `;
        });

        html += '</tbody></table></div>';
        element.innerHTML = html;
    }

    /**
     * 전술스킬 통계 렌더링
     */
    function renderTacticalSkillsSection(data, element) {
        const byTacticalSkillData = data.byTacticalSkillData || {};
        const byTacticalSkillTotal = data.byTacticalSkillTotal || {};

        console.log('Tactical skills data structure:', byTacticalSkillData);

        let html = '<div class="table-wrapper"><table class="sortable-table">';
        html += `
            <thead>
                <tr>
                    <th>이름</th>
                    <th>레벨</th>
                    <th>사용수</th>
                    <th>1위율</th>
                    <th>2위율</th>
                    <th>3위율</th>
                    <th>4위율</th>
                </tr>
            </thead>
            <tbody id="tactical-skill-tbody">
        `;

        // 전술스킬을 1+2레벨 합계로 정렬
        let skillsArray = [];
        for (const skillId in byTacticalSkillData) {
            const byTacticalSkill = byTacticalSkillData[skillId];
            // 1+2레벨 합계 계산
            const totalUses = (byTacticalSkillTotal[skillId] && byTacticalSkillTotal[skillId][1] ? byTacticalSkillTotal[skillId][1] : 0)
                + (byTacticalSkillTotal[skillId] && byTacticalSkillTotal[skillId][2] ? byTacticalSkillTotal[skillId][2] : 0);

            skillsArray.push({
                skillId: skillId,
                data: byTacticalSkill,
                totalUses: totalUses
            });
        }

        // 1+2레벨 합계로 내림차순 정렬
        skillsArray.sort((a, b) => b.totalUses - a.totalUses);

        let rowIndex = 0;
        skillsArray.forEach((skillObj) => {
            const byTacticalSkill = skillObj.data;

            // byTacticalSkill이 객체일 경우 Object.values()로 변환
            const skillItems = Array.isArray(byTacticalSkill) ? byTacticalSkill : Object.values(byTacticalSkill);

            skillItems.forEach(item => {
                const ranks = Object.values(item);
                if (ranks.length === 0) return;

                const firstItem = ranks[0];
                if (firstItem.game_rank > 4) return;

                const skillId = firstItem.tactical_skill_id;
                const skillLevel = firstItem.tactical_skill_level;
                const totalUses = byTacticalSkillTotal[skillId] && byTacticalSkillTotal[skillId][skillLevel]
                    ? byTacticalSkillTotal[skillId][skillLevel]
                    : 0;

                html += `
                    <tr class="tactical-skill-row" ${rowIndex >= 5 ? 'style="display: none;"' : ''}>
                        <td>
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <img src="/storage/TacticalSkill/${skillId}.png"
                                     alt="${firstItem.tactical_skill_name}"
                                     class="equipment-icon"
                                     onerror="this.style.display='none'">
                                ${firstItem.tactical_skill_name}
                            </div>
                        </td>
                        <td style="width: 40px; text-align: center;">${skillLevel}</td>
                        <td>${totalUses}</td>
                `;

                [1, 2, 3, 4].forEach(rank => {
                    const rankData = item[rank];
                    if (rankData) {
                        html += `
                            <td>
                                <div class="tooltip-wrap">
                                    ${formatPercent(rankData.game_rank_count_percent)}%
                                    <span class="tooltip-text">
                                        게임 수: ${rankData.game_rank_count}<br>
                                        평균 점수: ${rankData.positive_avg_mmr_gain}
                                    </span>
                                </div>
                            </td>
                        `;
                    } else {
                        html += '<td>-</td>';
                    }
                });

                html += '</tr>';
                rowIndex++;
            });
        });

        html += '</tbody></table></div>';
        html += '<button id="show-more-tactical-skills" class="show-more-button">더보기</button>';
        element.innerHTML = html;

        // 더보기 버튼 이벤트
        const showMoreBtn = element.querySelector('#show-more-tactical-skills');
        if (showMoreBtn) {
            showMoreBtn.addEventListener('click', function() {
                const hiddenRows = element.querySelectorAll('.tactical-skill-row[style*="display: none"]');
                hiddenRows.forEach(row => row.style.display = '');
                this.style.display = 'none';
            });
        }
    }

    /**
     * 장비 통계 렌더링
     */
    function renderEquipmentSection(data, element) {
        const byEquipmentData = data.byEquipmentData || {};
        const byEquipmentTotal = data.byEquipmentTotal || {};

        console.log('Equipment data structure:', byEquipmentData);

        const gradeTranslation = {
            'Mythic': '초월',
            'Legend': '전설',
            'Epic': '영웅',
            'Rare': '희귀',
            'Uncommon': '고급',
            'Common': '일반'
        };

        const grades = ['Mythic', 'Legend', 'Epic'];

        const itemTypeTranslation = {
            'Weapon': '무기',
            'Chest': '옷',
            'Head': '머리',
            'Arm': '팔/장식',
            'Leg': '다리'
        };

        let html = '';

        // 탭 링크 생성
        html += '<div class="tabs">';
        const types = ['Weapon', 'Chest', 'Head', 'Arm', 'Leg'];
        types.forEach((type, index) => {
            const tabId = type.toLowerCase() + '-stats';
            const activeClass = index === 0 ? 'active' : '';
            html += `<button class="tab-link ${activeClass}" onclick="openTab(event, '${tabId}')">${itemTypeTranslation[type]}</button>`;
        });
        html += '</div>';

        // 각 타입별 탭 컨텐츠
        types.forEach((type, typeIndex) => {
            const equipmentData = byEquipmentData[type] || [];
            const tabId = type.toLowerCase() + '-stats';
            const activeClass = typeIndex === 0 ? 'active' : '';

            html += `<div id="${tabId}" class="tab-content ${activeClass}">`;
            html += `<h3>${itemTypeTranslation[type]}</h3>`;

            // 등급 필터
            html += '<div class="grade-filter-container" style="margin-bottom: 10px;">';
            html += '<strong>등급 필터:</strong>';
            grades.forEach(gradeEn => {
                html += `
                    <label style="margin-right: 10px;">
                        <input type="checkbox" class="grade-filter-checkbox" value="${gradeEn}" data-tab-key="${tabId}" checked> ${gradeTranslation[gradeEn]}
                    </label>
                `;
            });
            html += '</div>';

            // 테이블
            html += '<div class="table-wrapper"><table class="sortable-table">';
            html += `
                <thead>
                    <tr>
                        <th>등급</th>
                        <th>이름</th>
                        <th>사용수</th>
                        <th>1위율</th>
                        <th>2위율</th>
                        <th>3위율</th>
                        <th>4위율</th>
                    </tr>
                </thead>
                <tbody>
            `;

            let preEquipmentName = '';
            let equipmentArray = Array.isArray(equipmentData) ? equipmentData : Object.values(equipmentData);

            // 사용수로 정렬
            equipmentArray = equipmentArray.sort((a, b) => {
                const aRanks = Object.values(a);
                const bRanks = Object.values(b);
                const aFirstItem = aRanks[0];
                const bFirstItem = bRanks[0];
                const aTotalUses = byEquipmentTotal[aFirstItem?.equipment_id] || 0;
                const bTotalUses = byEquipmentTotal[bFirstItem?.equipment_id] || 0;
                return bTotalUses - aTotalUses; // 내림차순
            });

            equipmentArray.forEach(item => {
                const ranks = Object.values(item);
                if (ranks.length === 0) return;

                const firstItem = ranks[0];
                if (firstItem.game_rank > 4) return;

                const equipmentId = firstItem.equipment_id;
                const equipmentName = firstItem.equipment_name;
                const itemGrade = firstItem.item_grade;
                const totalUses = byEquipmentTotal[equipmentId] || 0;

                html += `<tr data-equipment-id="${equipmentId}" data-grade="${itemGrade}">`;

                // 등급 (중복 장비명일 경우 빈칸)
                html += `<td>${preEquipmentName === equipmentName ? '' : (gradeTranslation[itemGrade] || itemGrade)}</td>`;

                // 이름 (중복일 경우 표시하지 않음)
                if (preEquipmentName !== equipmentName) {
                    html += `
                        <td>
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <img src="/storage/Equipment/${equipmentId}.png"
                                     alt="${equipmentName}"
                                     class="equipment-icon"
                                     onerror="this.onerror=null; this.src='/storage/Equipment/default.png';">
                                ${equipmentName}
                            </div>
                        </td>
                    `;
                } else {
                    html += '<td></td>';
                }

                // 사용수
                html += `<td>${totalUses}</td>`;

                // 1~4위율
                [1, 2, 3, 4].forEach(rank => {
                    const rankData = item[rank];
                    if (rankData) {
                        html += `
                            <td>
                                <div class="tooltip-wrap">
                                    ${formatPercent(rankData.game_rank_count_percent)}%
                                    <span class="tooltip-text">
                                        게임 수: ${rankData.game_rank_count}<br>
                                        평균 점수: ${rankData.positive_avg_mmr_gain}
                                    </span>
                                </div>
                            </td>
                        `;
                    } else {
                        html += '<td>-</td>';
                    }
                });

                html += '</tr>';
                preEquipmentName = equipmentName;
            });

            html += '</tbody></table></div>';
            html += '</div>'; // tab-content 종료
        });

        element.innerHTML = html;

        // 등급 필터 이벤트 리스너 추가
        setupGradeFilters(element);
    }

    /**
     * 등급 필터 설정
     */
    function setupGradeFilters(container) {
        const checkboxes = container.querySelectorAll('.grade-filter-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const tabKey = this.dataset.tabKey;
                const selectedGrades = Array.from(container.querySelectorAll(`.grade-filter-checkbox[data-tab-key="${tabKey}"]:checked`))
                    .map(cb => cb.value);

                const tabContent = container.querySelector(`#${tabKey}`);
                if (!tabContent) return;

                const rows = tabContent.querySelectorAll('tbody tr[data-grade]');
                rows.forEach(row => {
                    const grade = row.dataset.grade;
                    if (selectedGrades.includes(grade)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    }

    /**
     * 특성 통계 렌더링
     */
    function renderTraitsSection(data, element) {
        const byTraitData = data.byTraitData || {};
        const byTraitTotal = data.byTraitTotal || {};
        const traitCategories = data.traitCategories || [];

        console.log('Traits data structure:', byTraitData);

        // byTraitData를 배열로 변환하고 사용수로 정렬
        let traitsArray = Array.isArray(byTraitData) ? byTraitData : Object.values(byTraitData);

        // 사용수로 정렬
        traitsArray = traitsArray.sort((a, b) => {
            const aRanks = Object.values(a);
            const bRanks = Object.values(b);
            const aFirstItem = aRanks[0];
            const bFirstItem = bRanks[0];
            const aTotalUses = byTraitTotal[aFirstItem?.trait_id] || 0;
            const bTotalUses = byTraitTotal[bFirstItem?.trait_id] || 0;
            return bTotalUses - aTotalUses; // 내림차순
        });

        let html = '';

        // 특성 구분 필터
        html += '<div class="trait-is-main-filter-container" style="margin-bottom: 10px;">';
        html += '<strong>특성 구분 필터:</strong>';
        html += `
            <label style="margin-right: 10px;">
                <input type="checkbox" class="trait-is-main-filter-checkbox" value="1" checked> 메인
            </label>
            <label style="margin-right: 10px;">
                <input type="checkbox" class="trait-is-main-filter-checkbox" value="0" checked> 서브
            </label>
        `;
        html += '</div>';

        // 특성 분류 필터
        html += '<div class="trait-category-filter-container" style="margin-bottom: 10px;">';
        html += '<strong>특성 분류 필터:</strong>';
        traitCategories.forEach(category => {
            html += `
                <label style="margin-right: 10px;">
                    <input type="checkbox" class="trait-category-filter-checkbox" value="${category}" checked> ${category}
                </label>
            `;
        });
        html += '</div>';

        html += '<div class="table-wrapper"><table class="sortable-table">';
        html += `
            <thead>
                <tr>
                    <th>이름</th>
                    <th>분류</th>
                    <th>구분</th>
                    <th>사용수</th>
                    <th>1위율</th>
                    <th>2위율</th>
                    <th>3위율</th>
                    <th>4위율</th>
                </tr>
            </thead>
            <tbody id="trait-tbody">
        `;

        traitsArray.forEach((item, traitIndex) => {
            const ranks = Object.values(item);
            if (ranks.length === 0) return;

            const firstItem = ranks[0];
            if (firstItem.game_rank > 4) return;

            const traitId = firstItem.trait_id;
            const totalUses = byTraitTotal[traitId] || 0;
            const traitCategory = firstItem.trait_category;
            const isMain = firstItem.is_main ? 1 : 0;

            html += `
                <tr class="trait-row" data-category="${traitCategory}" data-is-main="${isMain}" ${traitIndex >= 10 ? 'style="display: none;"' : ''}>
                    <td>
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <img src="/storage/Trait/${traitId}.png"
                                 alt="${firstItem.trait_name}"
                                 class="equipment-icon"
                                 onerror="this.style.display='none'">
                            ${firstItem.trait_name}
                        </div>
                    </td>
                    <td>${traitCategory}</td>
                    <td>${firstItem.is_main ? '메인' : '서브'}</td>
                    <td>${totalUses}</td>
            `;

            [1, 2, 3, 4].forEach(rank => {
                const rankData = item[rank];
                if (rankData) {
                    html += `
                        <td>
                            <div class="tooltip-wrap">
                                ${formatPercent(rankData.game_rank_count_percent)}%
                                <span class="tooltip-text">
                                    게임 수: ${rankData.game_rank_count}<br>
                                    평균 점수: ${rankData.positive_avg_mmr_gain}
                                </span>
                            </div>
                        </td>
                    `;
                } else {
                    html += '<td>-</td>';
                }
            });

            html += '</tr>';
        });

        html += '</tbody></table></div>';
        html += '<button id="show-more-traits" class="show-more-button">더보기</button>';
        element.innerHTML = html;

        // 더보기 버튼 이벤트
        const showMoreBtn = element.querySelector('#show-more-traits');
        if (showMoreBtn) {
            showMoreBtn.addEventListener('click', function() {
                const hiddenRows = element.querySelectorAll('.trait-row[style*="display: none"]');
                hiddenRows.forEach(row => row.style.display = '');
                this.style.display = 'none';
            });
        }

        // 필터 이벤트 설정
        setupTraitFilters(element);
    }

    /**
     * 특성 필터 설정
     */
    function setupTraitFilters(container) {
        const isMainCheckboxes = container.querySelectorAll('.trait-is-main-filter-checkbox');
        const categoryCheckboxes = container.querySelectorAll('.trait-category-filter-checkbox');

        function applyFilters() {
            const selectedIsMain = Array.from(container.querySelectorAll('.trait-is-main-filter-checkbox:checked'))
                .map(cb => cb.value);
            const selectedCategories = Array.from(container.querySelectorAll('.trait-category-filter-checkbox:checked'))
                .map(cb => cb.value);

            const rows = container.querySelectorAll('#trait-tbody .trait-row');
            rows.forEach(row => {
                const category = row.dataset.category;
                const isMain = row.dataset.isMain;

                const categoryMatch = selectedCategories.length === 0 || selectedCategories.includes(category);
                const isMainMatch = selectedIsMain.length === 0 || selectedIsMain.includes(isMain);

                // display: none 스타일이 인라인으로 설정되어 있는지 확인
                const hasHiddenStyle = row.getAttribute('style')?.includes('display: none');

                if (categoryMatch && isMainMatch) {
                    if (!hasHiddenStyle) {
                        row.style.display = '';
                    }
                } else {
                    row.style.display = 'none';
                }
            });
        }

        isMainCheckboxes.forEach(cb => cb.addEventListener('change', applyFilters));
        categoryCheckboxes.forEach(cb => cb.addEventListener('change', applyFilters));
    }

    /**
     * Helper: 숫자 포맷팅
     */
    function formatNumber(value, decimals = 0) {
        if (value === null || value === undefined) return '0';
        return Number(value).toLocaleString('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    /**
     * Helper: 퍼센트 포맷팅
     */
    function formatPercent(value) {
        return formatNumber(value, 2);
    }

    // 필터 변경 시 페이지 리로드 (기존 동작 유지)
    const versionFilter = document.getElementById('sel-version-filter');
    const tierFilter = document.getElementById('sel-tier-filter');

    if (versionFilter) {
        versionFilter.addEventListener('change', function() {
            const newVersion = this.value;
            const currentTier = tierFilter?.value || minTier;
            window.location.href = `${window.location.pathname}?version=${newVersion}&min_tier=${currentTier}`;
        });
    }

    if (tierFilter) {
        tierFilter.addEventListener('change', function() {
            const newTier = this.value;
            const currentVersion = versionFilter?.value || version;
            window.location.href = `${window.location.pathname}?version=${currentVersion}&min_tier=${newTier}`;
        });
    }
});
