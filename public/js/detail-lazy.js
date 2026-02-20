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

    // 로드 상태 추적 (ranks는 서버에서 렌더링하므로 제외)
    const loadedSections = {
        tiers: false,
        tacticalSkills: false,
        equipment: false,
        traitStats: false
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

    // 모든 lazy 섹션 관찰 시작 (tiers는 버튼 클릭 시에만 로드하므로 제외)
    const sections = document.querySelectorAll('[data-lazy-section]');
    sections.forEach(section => {
        if (section.dataset.lazySection !== 'tiers') {
            observer.observe(section);
        }
    });

    // 티어 정보는 버튼 클릭 시에만 로드
    const toggleTierBtn = document.getElementById('toggle-tier-info');
    const tierContainer = document.getElementById('tier-info-container');

    if (toggleTierBtn && tierContainer) {
        toggleTierBtn.addEventListener('click', function() {
            const isHidden = tierContainer.style.display === 'none';

            if (isHidden) {
                tierContainer.style.display = 'block';
                toggleTierBtn.textContent = '▲ 접기';

                // 아직 로드되지 않았다면 로드
                if (!loadedSections.tiers) {
                    loadSection('tiers', tierContainer);
                    loadedSections.tiers = true;
                }
            } else {
                tierContainer.style.display = 'none';
                toggleTierBtn.textContent = '▼ 펼치기';
            }
        });
    }

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
                case 'traitStats':
                    // 특성 통계는 두 API를 병렬로 호출
                    await loadTraitStats(sectionElement);
                    return;
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
        }
    }

    /**
     * 특성 통계 (조합 + 개별) 로드
     */
    async function loadTraitStats(element) {
        try {
            // 두 API를 병렬로 호출
            const [traitsResponse, combinationsResponse] = await Promise.all([
                fetch(`/api/detail/${types}/traits?version=${version}&min_tier=${minTier}`),
                fetch(`/api/detail/${types}/trait-combinations?version=${version}&min_tier=${minTier}`)
            ]);

            if (!traitsResponse.ok || !combinationsResponse.ok) {
                throw new Error('API 호출 실패');
            }

            const traitsData = await traitsResponse.json();
            const combinationsData = await combinationsResponse.json();

            renderTraitStatsSection(traitsData, combinationsData, element);
        } catch (error) {
            console.error('Error loading trait stats:', error);
            showError(element, `특성 데이터를 불러오는데 실패했습니다: ${error.message}`);
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
                    <th>평균획득점수<span class="info-icon" data-tooltip="입장료를 차감하지 않고 게임 내에서 획득한 점수를 나타냅니다.">ⓘ</span></th>
                    <th>승률</th>
                    <th>TOP2</th>
                    <th>TOP4</th>
                    <th>막금구승률</th>
                    <th>평균TK</th>
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
                    <th>비율</th>
                    <th>평균획득점수<span class="info-icon" data-tooltip="입장료를 차감하지 않고 게임 내에서 획득한 점수를 나타냅니다.">ⓘ</span></th>
                    <th>평균TK</th>
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
     * 전술스킬 통계 렌더링 (탭 메뉴: 통합 + 레벨별)
     */
    function renderTacticalSkillsSection(data, element) {
        const aggregatedData = data.aggregatedData || [];
        const aggregatedBySkill = data.aggregatedBySkill || [];

        if (aggregatedData.length === 0 && aggregatedBySkill.length === 0) {
            element.innerHTML = '<p style="text-align: center; color: #999;">집계된 전술스킬 데이터가 없습니다.</p>';
            return;
        }

        let html = '';

        // 탭 메뉴
        html += '<div class="tabs">';
        html += '<button class="tab-link active" onclick="openTacticalSkillTab(event, \'tactical-skill-combined\')">통합</button>';
        html += '<button class="tab-link" onclick="openTacticalSkillTab(event, \'tactical-skill-by-level\')">레벨별</button>';
        html += '</div>';

        // 통합 탭 (기본 활성화)
        html += '<div id="tactical-skill-combined" class="tab-content active">';
        html += renderTacticalSkillCombinedContent(aggregatedBySkill);
        html += '</div>';

        // 레벨별 탭
        html += '<div id="tactical-skill-by-level" class="tab-content">';
        html += renderTacticalSkillByLevelContent(aggregatedData);
        html += '</div>';

        element.innerHTML = html;

        // 탭 전환 함수 등록
        window.openTacticalSkillTab = function(evt, tabName) {
            const tabContents = element.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.style.display = 'none';
                content.classList.remove('active');
            });

            const tabLinks = element.querySelectorAll('.tab-link');
            tabLinks.forEach(link => link.classList.remove('active'));

            const targetTab = element.querySelector(`#${tabName}`);
            if (targetTab) {
                targetTab.style.display = 'block';
                targetTab.classList.add('active');
            }
            if (evt && evt.currentTarget) {
                evt.currentTarget.classList.add('active');
            }
        };

        // 툴팁 이벤트 설정
        setupTooltips(element);
    }

    /**
     * 전술스킬 통합 통계 (레벨 구분 없음)
     */
    function renderTacticalSkillCombinedContent(aggregatedBySkill) {
        if (aggregatedBySkill.length === 0) {
            return '<p style="text-align: center; color: #999;">집계된 전술스킬 데이터가 없습니다.</p>';
        }

        let html = '<div class="tactical-skill-scroll-container" style="max-height: 500px; overflow-y: auto; border: 1px solid #333;">';
        html += '<div class="table-wrapper" style="margin: 0;"><table class="sortable-table">';
        html += `
            <thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 10;">
                <tr>
                    <th>이름</th>
                    <th>사용수</th>
                    <th>평균획득점수<span class="info-icon" data-tooltip="입장료를 차감하지 않고 게임 내에서 획득한 점수를 나타냅니다.">ⓘ</span></th>
                    <th>승률</th>
                    <th class="hide-on-mobile">TOP2</th>
                    <th class="hide-on-mobile">TOP4</th>
                    <th class="hide-on-mobile hide-on-tablet">막금구승률</th>
                    <th class="hide-on-mobile hide-on-tablet">평균TK</th>
                    <th class="hide-on-mobile">이득확률</th>
                    <th class="hide-on-mobile">손실확률</th>
                </tr>
            </thead>
            <tbody>
        `;

        aggregatedBySkill.forEach((item) => {
            const skillTooltip = (item.tactical_skill_tooltip || '').replace(/\n/g, '<br>');
            const tooltipContent = skillTooltip
                ? `<strong>${item.tactical_skill_name}</strong><br><br>${skillTooltip}`
                : item.tactical_skill_name;

            html += `
                <tr class="tactical-skill-row">
                    <td>
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <div class="tooltip-wrap">
                                <img src="/storage/TacticalSkill/${item.tactical_skill_id}.png"
                                     alt="${item.tactical_skill_name}"
                                     class="equipment-icon"
                                     onerror="this.style.display='none'">
                                <span class="tooltip-text">${tooltipContent}</span>
                            </div>
                            ${item.tactical_skill_name}
                        </div>
                    </td>
                    <td class="number">${formatNumber(item.game_count)}</td>
                    <td class="number">${formatNumber(item.avg_mmr_gain, 1)}</td>
                    <td class="number">
                        <div>${formatPercent(item.top1_count_percent)}%</div>
                        <div class="sub-stat">${formatNumber(item.top1_count)}</div>
                    </td>
                    <td class="hide-on-mobile number">
                        <div>${formatPercent(item.top2_count_percent)}%</div>
                        <div class="sub-stat">${formatNumber(item.top2_count)}</div>
                    </td>
                    <td class="hide-on-mobile number">
                        <div>${formatPercent(item.top4_count_percent)}%</div>
                        <div class="sub-stat">${formatNumber(item.top4_count)}</div>
                    </td>
                    <td class="hide-on-mobile hide-on-tablet number">${formatPercent(item.endgame_win_percent)}%</td>
                    <td class="hide-on-mobile hide-on-tablet number">${formatNumber(item.avg_team_kill_score, 2)}</td>
                    <td class="hide-on-mobile number">
                        <div>${formatPercent(item.positive_game_count_percent)}%</div>
                        <div class="sub-stat">+${formatNumber(item.positive_avg_mmr_gain, 1)}</div>
                    </td>
                    <td class="hide-on-mobile number">
                        <div>${formatPercent(item.negative_game_count_percent)}%</div>
                        <div class="sub-stat">${formatNumber(item.negative_avg_mmr_gain, 1)}</div>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table></div></div>';
        return html;
    }

    /**
     * 전술스킬 레벨별 통계
     */
    function renderTacticalSkillByLevelContent(aggregatedData) {
        if (aggregatedData.length === 0) {
            return '<p style="text-align: center; color: #999;">집계된 전술스킬 데이터가 없습니다.</p>';
        }

        let html = '<div class="tactical-skill-scroll-container" style="max-height: 500px; overflow-y: auto; border: 1px solid #333;">';
        html += '<div class="table-wrapper" style="margin: 0;"><table class="sortable-table">';
        html += `
            <thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 10;">
                <tr>
                    <th>이름</th>
                    <th>사용수</th>
                    <th>평균획득점수<span class="info-icon" data-tooltip="입장료를 차감하지 않고 게임 내에서 획득한 점수를 나타냅니다.">ⓘ</span></th>
                    <th>승률</th>
                    <th class="hide-on-mobile">TOP2</th>
                    <th class="hide-on-mobile">TOP4</th>
                    <th class="hide-on-mobile hide-on-tablet">막금구승률</th>
                    <th class="hide-on-mobile hide-on-tablet">평균TK</th>
                    <th class="hide-on-mobile">이득확률</th>
                    <th class="hide-on-mobile">손실확률</th>
                </tr>
            </thead>
            <tbody>
        `;

        aggregatedData.forEach((item) => {
            const skillTooltip = (item.tactical_skill_tooltip || '').replace(/\n/g, '<br>');
            const tooltipContent = skillTooltip
                ? `<strong>${item.tactical_skill_name} Lv ${item.tactical_skill_level}</strong><br><br>${skillTooltip}`
                : `${item.tactical_skill_name} Lv ${item.tactical_skill_level}`;

            html += `
                <tr class="tactical-skill-row">
                    <td>
                        <div style="display: flex; align-items: center; gap: 5px;">
                            <div class="tooltip-wrap">
                                <img src="/storage/TacticalSkill/${item.tactical_skill_id}.png"
                                     alt="${item.tactical_skill_name}"
                                     class="equipment-icon"
                                     onerror="this.style.display='none'">
                                <span class="tooltip-text">${tooltipContent}</span>
                            </div>
                            ${item.tactical_skill_name} Lv ${item.tactical_skill_level}
                        </div>
                    </td>
                    <td class="number">${formatNumber(item.game_count)}</td>
                    <td class="number">${formatNumber(item.avg_mmr_gain, 1)}</td>
                    <td class="number">
                        <div>${formatPercent(item.top1_count_percent)}%</div>
                        <div class="sub-stat">${formatNumber(item.top1_count)}</div>
                    </td>
                    <td class="hide-on-mobile number">
                        <div>${formatPercent(item.top2_count_percent)}%</div>
                        <div class="sub-stat">${formatNumber(item.top2_count)}</div>
                    </td>
                    <td class="hide-on-mobile number">
                        <div>${formatPercent(item.top4_count_percent)}%</div>
                        <div class="sub-stat">${formatNumber(item.top4_count)}</div>
                    </td>
                    <td class="hide-on-mobile hide-on-tablet number">${formatPercent(item.endgame_win_percent)}%</td>
                    <td class="hide-on-mobile hide-on-tablet number">${formatNumber(item.avg_team_kill_score, 2)}</td>
                    <td class="hide-on-mobile number">
                        <div>${formatPercent(item.positive_game_count_percent)}%</div>
                        <div class="sub-stat">+${formatNumber(item.positive_avg_mmr_gain, 1)}</div>
                    </td>
                    <td class="hide-on-mobile number">
                        <div>${formatPercent(item.negative_game_count_percent)}%</div>
                        <div class="sub-stat">${formatNumber(item.negative_avg_mmr_gain, 1)}</div>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table></div></div>';
        return html;
    }

    /**
     * 장비 통계 렌더링 (특성 통계와 동일한 열 구성)
     */
    function renderEquipmentSection(data, element) {
        const aggregatedData = data.aggregatedData || {};

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

        const itemType3Translation = {
            'mt': '운석',
            'tl': '생명의나무',
            'ml': '미스릴',
            'fc': '포스코어',
            'vf': '혈액샘플'
        };

        const itemType3Codes = ['mt', 'tl', 'ml', 'fc', 'vf'];

        let html = '';

        // 탭 링크 생성
        html += '<div class="tabs">';
        const types = ['Weapon', 'Chest', 'Head', 'Arm', 'Leg'];
        types.forEach((type, index) => {
            const tabId = type.toLowerCase() + '-stats';
            const activeClass = index === 0 ? 'active' : '';
            html += `<button class="tab-link ${activeClass}" onclick="openEquipmentTab(event, '${tabId}')">${itemTypeTranslation[type]}</button>`;
        });
        html += '</div>';

        // 각 타입별 탭 컨텐츠
        types.forEach((type, typeIndex) => {
            const equipmentList = aggregatedData[type] || [];
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

            // 재료 필터
            html += '<div class="type3-filter-container" style="margin-bottom: 10px;">';
            html += '<strong>재료 필터:</strong>';
            html += `
                <label style="margin-right: 10px;">
                    <input type="checkbox" class="type3-filter-checkbox" value="__none__" data-tab-key="${tabId}" checked> 없음
                </label>
            `;
            itemType3Codes.forEach(code => {
                html += `
                    <label style="margin-right: 10px;">
                        <input type="checkbox" class="type3-filter-checkbox" value="${code}" data-tab-key="${tabId}" checked> ${itemType3Translation[code]}
                    </label>
                `;
            });
            html += '</div>';

            // 스크롤 가능한 영역으로 감싸기
            html += '<div class="equipment-scroll-container" style="max-height: 500px; overflow-y: auto; border: 1px solid #333;">';
            html += '<div class="table-wrapper" style="margin: 0;"><table class="sortable-table">';
            html += `
                <thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 10;">
                    <tr>
                        <th>등급</th>
                        <th>이름</th>
                        <th>사용수</th>
                        <th>평균획득점수<span class="info-icon" data-tooltip="입장료를 차감하지 않고 게임 내에서 획득한 점수를 나타냅니다.">ⓘ</span></th>
                        <th>승률</th>
                        <th class="hide-on-mobile">TOP2</th>
                        <th class="hide-on-mobile">TOP4</th>
                        <th class="hide-on-mobile hide-on-tablet">막금구승률</th>
                        <th class="hide-on-mobile hide-on-tablet">평균TK</th>
                        <th class="hide-on-mobile">이득확률</th>
                        <th class="hide-on-mobile">손실확률</th>
                    </tr>
                </thead>
                <tbody>
            `;

            equipmentList.forEach(item => {
                // 장비 정보 툴팁 생성
                const hasStats = item.equipment_stats && Array.isArray(item.equipment_stats) && item.equipment_stats.length > 0;
                const hasSkills = item.equipment_skills && Array.isArray(item.equipment_skills) && item.equipment_skills.length > 0;

                let tooltipContent = '';
                if (hasStats) {
                    item.equipment_stats.forEach(stat => {
                        tooltipContent += `${stat.text}: ${stat.value}<br>`;
                    });
                } else {
                    tooltipContent += '장비 정보 없음';
                }

                if (hasSkills) {
                    tooltipContent += '<br>';
                    item.equipment_skills.forEach((skill, idx) => {
                        tooltipContent += `<strong style="color: #ffd700;">${skill.name}</strong><br>`;
                        tooltipContent += `${skill.description}<br>`;
                        if (idx < item.equipment_skills.length - 1) {
                            tooltipContent += '<br>';
                        }
                    });
                }

                html += `
                    <tr data-equipment-id="${item.equipment_id}" data-grade="${item.item_grade}" data-item-type3="${item.item_type3 || ''}">
                        <td>${gradeTranslation[item.item_grade] || item.item_grade}</td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <div class="tooltip-wrap">
                                    <img src="/storage/Equipment/${item.equipment_id}.png"
                                         alt="${item.equipment_name}"
                                         class="equipment-icon"
                                         onerror="this.onerror=null; this.src='/storage/Equipment/default.png';">
                                    <span class="tooltip-text">${tooltipContent}</span>
                                </div>
                                ${item.equipment_name}
                            </div>
                        </td>
                        <td class="number">${formatNumber(item.game_count)}</td>
                        <td class="number">${formatNumber(item.avg_mmr_gain, 1)}</td>
                        <td class="number">
                            <div>${formatPercent(item.top1_count_percent)}%</div>
                            <div class="sub-stat">${formatNumber(item.top1_count)}</div>
                        </td>
                        <td class="hide-on-mobile number">
                            <div>${formatPercent(item.top2_count_percent)}%</div>
                            <div class="sub-stat">${formatNumber(item.top2_count)}</div>
                        </td>
                        <td class="hide-on-mobile number">
                            <div>${formatPercent(item.top4_count_percent)}%</div>
                            <div class="sub-stat">${formatNumber(item.top4_count)}</div>
                        </td>
                        <td class="hide-on-mobile hide-on-tablet number">${formatPercent(item.endgame_win_percent)}%</td>
                        <td class="hide-on-mobile hide-on-tablet number">${formatNumber(item.avg_team_kill_score, 2)}</td>
                        <td class="hide-on-mobile number">
                            <div>${formatPercent(item.positive_game_count_percent)}%</div>
                            <div class="sub-stat">+${formatNumber(item.positive_avg_mmr_gain, 1)}</div>
                        </td>
                        <td class="hide-on-mobile number">
                            <div>${formatPercent(item.negative_game_count_percent)}%</div>
                            <div class="sub-stat">${formatNumber(item.negative_avg_mmr_gain, 1)}</div>
                        </td>
                    </tr>
                `;
            });

            html += '</tbody></table></div></div>';
            html += '</div>'; // tab-content 종료
        });

        element.innerHTML = html;

        // 장비 탭 전용 함수 등록 (장비 영역 내에서만 탭 전환)
        window.openEquipmentTab = function(evt, tabName) {
            // 장비 영역 내의 탭만 제어
            const equipmentContainer = element;
            const tabContents = equipmentContainer.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.style.display = 'none';
                content.classList.remove('active');
            });

            const tabLinks = equipmentContainer.querySelectorAll('.tab-link');
            tabLinks.forEach(link => link.classList.remove('active'));

            const targetTab = equipmentContainer.querySelector(`#${tabName}`);
            if (targetTab) {
                targetTab.style.display = 'block';
                targetTab.classList.add('active');
            }
            if (evt && evt.currentTarget) {
                evt.currentTarget.classList.add('active');
            }
        };

        // 등급 필터 이벤트 리스너 추가
        setupGradeFilters(element);

        // 툴팁 포지셔닝 이벤트 추가
        setupTooltips(element);
    }

    /**
     * 등급 필터 + 재료 필터 설정
     */
    function setupGradeFilters(container) {
        function applyEquipmentFilters(tabKey) {
            const selectedGrades = Array.from(container.querySelectorAll(`.grade-filter-checkbox[data-tab-key="${tabKey}"]:checked`))
                .map(cb => cb.value);
            const selectedType3 = Array.from(container.querySelectorAll(`.type3-filter-checkbox[data-tab-key="${tabKey}"]:checked`))
                .map(cb => cb.value);

            const tabContent = container.querySelector(`#${tabKey}`);
            if (!tabContent) return;

            const rows = tabContent.querySelectorAll('tbody tr[data-grade]');
            rows.forEach(row => {
                const grade = row.dataset.grade;
                const type3 = row.dataset.itemType3 || '';

                const gradeMatch = selectedGrades.includes(grade);
                // type3가 비어있으면(null) '__none__'으로 매칭
                const type3Value = type3 === '' ? '__none__' : type3;
                const type3Match = selectedType3.includes(type3Value);

                row.style.display = (gradeMatch && type3Match) ? '' : 'none';
            });
        }

        const gradeCheckboxes = container.querySelectorAll('.grade-filter-checkbox');
        gradeCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                applyEquipmentFilters(this.dataset.tabKey);
            });
        });

        const type3Checkboxes = container.querySelectorAll('.type3-filter-checkbox');
        type3Checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                applyEquipmentFilters(this.dataset.tabKey);
            });
        });
    }

    /**
     * 특성 통계 (탭 메뉴: 특성 조합 통계 + 특성 개별 통계)
     */
    function renderTraitStatsSection(traitsData, combinationsData, element) {
        let html = '';

        // 탭 메뉴
        html += '<div class="tabs">';
        html += '<button class="tab-link active" onclick="openTraitTab(event, \'trait-combination-stats\')">특성 조합</button>';
        html += '<button class="tab-link" onclick="openTraitTab(event, \'trait-individual-stats\')">특성 개별</button>';
        html += '</div>';

        // 특성 조합 통계 탭 (기본 활성화)
        html += '<div id="trait-combination-stats" class="tab-content active">';
        html += renderTraitCombinationsContent(combinationsData);
        html += '</div>';

        // 특성 개별 통계 탭
        html += '<div id="trait-individual-stats" class="tab-content">';
        html += renderTraitsContent(traitsData);
        html += '</div>';

        element.innerHTML = html;

        // 탭 전환 함수를 전역으로 등록
        window.openTraitTab = function(evt, tabName) {
            const tabContents = element.querySelectorAll('.tab-content');
            tabContents.forEach(content => content.classList.remove('active'));

            const tabLinks = element.querySelectorAll('.tab-link');
            tabLinks.forEach(link => link.classList.remove('active'));

            element.querySelector(`#${tabName}`).classList.add('active');
            evt.currentTarget.classList.add('active');
        };

        // 필터 이벤트 설정
        setupTraitFilters(element);

        // 툴팁 이벤트 설정
        setupTooltips(element);
    }

    /**
     * 특성 조합 통계 컨텐츠 생성 (상위 12개만 표시)
     */
    function renderTraitCombinationsContent(data) {
        const combinationsData = data.data || [];
        const traits = data.traits || {};

        if (combinationsData.length === 0) {
            return '<p style="text-align: center; color: #999;">집계된 특성 조합 데이터가 없습니다.</p>';
        }

        // 상위 12개만 표시
        const displayData = combinationsData.slice(0, 12);

        let html = '<div class="table-wrapper"><table class="sortable-table">';
        html += `
            <thead>
                <tr>
                    <th>특성 조합</th>
                    <th>사용수</th>
                    <th>평균획득점수<span class="info-icon" data-tooltip="입장료를 차감하지 않고 게임 내에서 획득한 점수를 나타냅니다.">ⓘ</span></th>
                    <th>승률</th>
                    <th class="hide-on-mobile">TOP2</th>
                    <th class="hide-on-mobile">TOP4</th>
                    <th class="hide-on-mobile hide-on-tablet">막금구승률</th>
                    <th class="hide-on-mobile hide-on-tablet">평균TK</th>
                    <th class="hide-on-mobile">이득확률</th>
                    <th class="hide-on-mobile">손실확률</th>
                </tr>
            </thead>
            <tbody id="trait-combination-tbody">
        `;

        displayData.forEach((item, index) => {
            const traitIds = item.trait_ids ? item.trait_ids.split(',') : [];

            // 특성 정렬: is_main=1 -> 같은 category의 is_main=0 -> 나머지
            const sortedTraitIds = [...traitIds].sort((a, b) => {
                const traitA = traits[a];
                const traitB = traits[b];

                const isMainA = traitA && traitA.is_main == 1;
                const isMainB = traitB && traitB.is_main == 1;

                if (isMainA && !isMainB) return -1;
                if (!isMainA && isMainB) return 1;

                if (!isMainA && !isMainB) {
                    const mainTrait = traitIds.map(id => traits[id]).find(t => t && t.is_main == 1);
                    const mainCategory = mainTrait ? mainTrait.category : null;

                    const categoryA = traitA ? traitA.category : null;
                    const categoryB = traitB ? traitB.category : null;

                    const matchA = categoryA === mainCategory;
                    const matchB = categoryB === mainCategory;

                    if (matchA && !matchB) return -1;
                    if (!matchA && matchB) return 1;
                }

                return 0;
            });

            // 특성 아이콘들 생성
            let traitIconsHtml = '<div class="trait-icons-container" style="display: flex; gap: 2px; align-items: center;">';
            sortedTraitIds.forEach(traitId => {
                const trait = traits[traitId];
                const traitName = trait ? trait.name : `특성 ${traitId}`;
                const traitTooltip = trait && trait.tooltip ? trait.tooltip.replace(/\n/g, '<br>') : '';
                const isMain = trait && trait.is_main == 1;
                const iconSize = isMain ? '31px' : '23px';
                const borderStyle = isMain ? 'border: 2px solid #ffd700;' : '';
                const tooltipContent = traitTooltip
                    ? `<strong>${traitName}</strong>${isMain ? ' (메인)' : ' (서브)'}<br><br>${traitTooltip}`
                    : `${traitName}${isMain ? ' (메인)' : ' (서브)'}`;
                traitIconsHtml += `
                    <div class="tooltip-wrap">
                        <img src="/storage/Trait/${traitId}.png"
                             alt="${traitName}"
                             class="trait-combination-icon"
                             style="width: ${iconSize}; height: ${iconSize}; ${borderStyle}"
                             onerror="this.style.display='none'">
                        <span class="tooltip-text">${tooltipContent}</span>
                    </div>
                `;
            });
            traitIconsHtml += '</div>';

            html += `
                <tr class="trait-combination-row">
                    <td>${traitIconsHtml}</td>
                    <td class="number">${formatNumber(item.game_count)}</td>
                    <td class="number">${formatNumber(item.avg_mmr_gain, 1)}</td>
                    <td class="number">
                        <div>${formatPercent(item.top1_count_percent)}%</div>
                        <div class="sub-stat">${formatNumber(item.top1_count)}</div>
                    </td>
                    <td class="hide-on-mobile number">
                        <div>${formatPercent(item.top2_count_percent)}%</div>
                        <div class="sub-stat">${formatNumber(item.top2_count)}</div>
                    </td>
                    <td class="hide-on-mobile number">
                        <div>${formatPercent(item.top4_count_percent)}%</div>
                        <div class="sub-stat">${formatNumber(item.top4_count)}</div>
                    </td>
                    <td class="hide-on-mobile hide-on-tablet number">${formatPercent(item.endgame_win_percent)}%</td>
                    <td class="hide-on-mobile hide-on-tablet number">${formatNumber(item.avg_team_kill_score, 2)}</td>
                    <td class="hide-on-mobile number">
                        <div>${formatPercent(item.positive_game_count_percent)}%</div>
                        <div class="sub-stat">+${formatNumber(item.positive_avg_mmr_gain, 1)}</div>
                    </td>
                    <td class="hide-on-mobile number">
                        <div>${formatPercent(item.negative_game_count_percent)}%</div>
                        <div class="sub-stat">${formatNumber(item.negative_avg_mmr_gain, 1)}</div>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table></div>';

        return html;
    }

    /**
     * 특성 개별 통계 컨텐츠 생성 (전체 표시 + 스크롤 영역)
     */
    function renderTraitsContent(data) {
        const aggregatedData = data.aggregatedData || [];
        const traitCategories = data.traitCategories || [];

        if (aggregatedData.length === 0) {
            return '<p style="text-align: center; color: #999;">집계된 특성 데이터가 없습니다.</p>';
        }

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

        // 스크롤 가능한 영역으로 감싸기
        html += '<div class="trait-scroll-container" style="max-height: 500px; overflow-y: auto; border: 1px solid #333;">';
        html += '<div class="table-wrapper" style="margin: 0;"><table class="sortable-table">';
        html += `
            <thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 10;">
                <tr>
                    <th>특성</th>
                    <th>사용수</th>
                    <th>평균획득점수<span class="info-icon" data-tooltip="입장료를 차감하지 않고 게임 내에서 획득한 점수를 나타냅니다.">ⓘ</span></th>
                    <th>승률</th>
                    <th class="hide-on-mobile">TOP2</th>
                    <th class="hide-on-mobile">TOP4</th>
                    <th class="hide-on-mobile hide-on-tablet">막금구승률</th>
                    <th class="hide-on-mobile hide-on-tablet">평균TK</th>
                    <th class="hide-on-mobile">이득확률</th>
                    <th class="hide-on-mobile">손실확률</th>
                </tr>
            </thead>
            <tbody id="trait-tbody">
        `;

        // 전체 데이터 표시 (숨김 없음)
        aggregatedData.forEach((item, index) => {
            const traitId = item.trait_id;
            const isMain = item.is_main ? 1 : 0;
            const iconSize = item.is_main ? '31px' : '23px';
            const borderStyle = item.is_main ? 'border: 2px solid #ffd700;' : '';
            const traitTooltip = (item.trait_tooltip || '').replace(/\n/g, '<br>');
            const tooltipContent = traitTooltip
                ? `<strong>${item.trait_name}</strong>${item.is_main ? ' (메인)' : ' (서브)'}<br>분류: ${item.trait_category}<br><br>${traitTooltip}`
                : `${item.trait_name}${item.is_main ? ' (메인)' : ' (서브)'}<br>분류: ${item.trait_category}`;

            html += `
                <tr class="trait-row" data-category="${item.trait_category}" data-is-main="${isMain}">
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div class="tooltip-wrap">
                                <img src="/storage/Trait/${traitId}.png"
                                     alt="${item.trait_name}"
                                     class="equipment-icon"
                                     style="width: ${iconSize}; height: ${iconSize}; ${borderStyle}"
                                     onerror="this.style.display='none'">
                                <span class="tooltip-text">${tooltipContent}</span>
                            </div>
                            <span>${item.trait_name}</span>
                        </div>
                    </td>
                    <td class="number">${formatNumber(item.game_count)}</td>
                    <td class="number">${formatNumber(item.avg_mmr_gain, 1)}</td>
                    <td class="number">
                        <div>${formatPercent(item.top1_count_percent)}%</div>
                        <div class="sub-stat">${formatNumber(item.top1_count)}</div>
                    </td>
                    <td class="hide-on-mobile number">
                        <div>${formatPercent(item.top2_count_percent)}%</div>
                        <div class="sub-stat">${formatNumber(item.top2_count)}</div>
                    </td>
                    <td class="hide-on-mobile number">
                        <div>${formatPercent(item.top4_count_percent)}%</div>
                        <div class="sub-stat">${formatNumber(item.top4_count)}</div>
                    </td>
                    <td class="hide-on-mobile hide-on-tablet number">${formatPercent(item.endgame_win_percent)}%</td>
                    <td class="hide-on-mobile hide-on-tablet number">${formatNumber(item.avg_team_kill_score, 2)}</td>
                    <td class="hide-on-mobile number">
                        <div>${formatPercent(item.positive_game_count_percent)}%</div>
                        <div class="sub-stat">+${formatNumber(item.positive_avg_mmr_gain, 1)}</div>
                    </td>
                    <td class="hide-on-mobile number">
                        <div>${formatPercent(item.negative_game_count_percent)}%</div>
                        <div class="sub-stat">${formatNumber(item.negative_avg_mmr_gain, 1)}</div>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table></div></div>';

        return html;
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

                if (categoryMatch && isMainMatch) {
                    row.classList.remove('filtered-hidden');
                } else {
                    row.classList.add('filtered-hidden');
                }
            });
        }

        isMainCheckboxes.forEach(cb => cb.addEventListener('change', applyFilters));
        categoryCheckboxes.forEach(cb => cb.addEventListener('change', applyFilters));
    }

    /**
     * 툴팁 포지셔닝 - 화면 경계 체크
     */
    function positionTooltip(wrap, tooltip) {
        const rect = wrap.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        const tooltipWidth = tooltip.offsetWidth || 250;
        const tooltipHeight = tooltip.offsetHeight || 100;

        let left = rect.left + (rect.width / 2);
        let top = rect.top - 10;

        const halfWidth = tooltipWidth / 2;
        const padding = 10;

        if (left - halfWidth < padding) {
            left = padding + halfWidth;
        } else if (left + halfWidth > viewportWidth - padding) {
            left = viewportWidth - padding - halfWidth;
        }

        if (top - tooltipHeight < padding) {
            top = rect.bottom + 10;
            tooltip.style.transform = 'translate(-50%, 0)';
            tooltip.classList.add('tooltip-bottom');
            tooltip.classList.remove('tooltip-top');
        } else {
            tooltip.style.transform = 'translate(-50%, -100%)';
            tooltip.classList.add('tooltip-top');
            tooltip.classList.remove('tooltip-bottom');
        }

        tooltip.style.left = left + 'px';
        tooltip.style.top = top + 'px';
    }

    /**
     * 툴팁 포지셔닝 설정
     */
    function setupTooltips(container) {
        const tooltipWraps = container.querySelectorAll('.tooltip-wrap');
        tooltipWraps.forEach(wrap => {
            const tooltip = wrap.querySelector('.tooltip-text');
            if (!tooltip) return;

            wrap.addEventListener('mouseenter', function(e) {
                positionTooltip(this, tooltip);
            });

            wrap.addEventListener('touchstart', function(e) {
                document.querySelectorAll('.tooltip-text.touch-visible').forEach(t => {
                    if (t !== tooltip) {
                        t.classList.remove('touch-visible');
                    }
                });
                positionTooltip(this, tooltip);
                tooltip.classList.toggle('touch-visible');
            }, { passive: true });
        });
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
