# Lumia Lab V2 프로젝트 구조 분석

## 프로젝트 개요
- **프레임워크**: Laravel (PHP)
- **프로젝트 타입**: 게임 데이터 분석 웹 애플리케이션 (Lumia 게임 관련)

## 데이터베이스 구조

### 주요 테이블

#### 1. 게임 결과 관련
- **game_results**: 게임 결과 메인 테이블
  - 게임 ID, 유저 ID, MMR 정보 (시작전/후/변동치/입장료)
  - 캐릭터 ID, 무기 ID
  - 킬/데스/어시스트 점수
  - 게임 시작 시간, 버전 정보

- **game_result_skill_orders**: 스킬 찍은 순서
- **game_result_trait_orders**: 특성 정보 (메인/서브)
- **game_result_equipment_orders**: 장비 아이템 순서
- **game_result_first_equipment_orders**: 첫 장비 아이템 정보

#### 2. 게임 데이터 관련
- **characters**: 캐릭터 정보
  - HP, MP, 공격력, 방어력 등 스탯
  - 레벨별 증가치
  - 공격속도, 이동속도, 시야 범위

- **equipments**: 장비 아이템 정보
  - 아이템 타입, 등급
  - 각종 스탯 효과 (공격력, 방어력, 스킬 증폭 등)
  - 특수 효과 (생명력 흡수, 치명타, 쿨다운 감소 등)

- **traits**: 특성 정보
- **tactical_skills**: 전술 스킬 정보
- **character_skills**: 캐릭터 스킬 정보

#### 3. 통계/요약 테이블
- **game_results_summary**: 게임 결과 요약
- **game_results_rank_summary**: 랭크별 요약
- **game_results_equipment_summary**: 장비별 요약
- **game_results_equipment_main_summary**: 메인 장비 요약
- **game_results_tactical_skill_summary**: 전술 스킬 요약
- **game_results_trait_summary**: 특성 요약 (개별 특성)
- **game_results_trait_combination_summary**: 특성 조합 요약 (캐릭터별 특성 조합 통계)
- **game_results_first_equipment_main_summary**: 첫 장비 메인 요약

#### 4. 기타
- **users**: 사용자 정보
- **version_history**: 버전 이력
- **rank_ranges**: 랭크 범위 정보
- **job_status**: 작업 상태

## 모델 구조
- **DynamicModel**: 커스텀 베이스 모델
- 각 테이블별 Eloquent 모델 존재
- GameResult 모델이 메인 모델로 사용됨

## 라우팅 구조

### Web 라우트
- `/` , `/main`: 메인 페이지 (MainController)
- `/equipment`: 장비 페이지 (EquipmentController)
- `/equipment-first`: 첫 장비 페이지 (EquipmentFirstController)
- `/detail/{types}`: 상세 페이지

### API 라우트
- `/api/character`: 캐릭터 정보 조회
- `/api/equipment`: 장비 정보 조회
- `/api/item`: 아이템 정보 조회
- `/api/skill`: 스킬 정보 조회
- `/api/trait`: 특성 정보 조회

## 주요 기능
1. 게임 결과 데이터 수집 및 저장
2. 캐릭터/장비/스킬 등 게임 데이터 관리
3. 게임 통계 분석 및 요약
4. MMR 변동 추적
5. 버전별 데이터 관리

## Filament 관리자 패널
- **버전**: Filament 4 (v4.x)
- **중요**: Filament 4 사용 중! v3 문법 사용 금지

### Filament 4 주요 변경사항
1. **Form → Schema**: `Form` 대신 `Schema` 사용
   - `form(Form $form)` → `form(Schema $schema)`
   - `$form->schema([])` → `$schema->components([])`

2. **Actions 네임스페이스**:
   - `Filament\Tables\Actions\*` → `Filament\Actions\*`
   - `->actions([])` → `->recordActions([])`
   - `->bulkActions([])` → `->toolbarActions([])`

3. **Icon 속성 타입**:
   - `protected static ?string $navigationIcon` → `protected static string|BackedEnum|null $navigationIcon`
   - Heroicon enum 사용: `Heroicon::OutlineUser` 등

4. **Get/Set 유틸리티**:
   - `Forms\Get` → `Schemas\Components\Utilities\Get`
   - `Forms\Set` → `Schemas\Components\Utilities\Set`
   - 클로저 내에서 타입 힌트 필수

5. **Section 컴포넌트**:
   - Schema 내부에서는 Section 사용 불가, 평면적인 컴포넌트 구조 사용

### 관리 페이지 구조
- Characters (캐릭터 관리)
- Equipment (장비 관리)
- VersionHistories (버전 관리)
  - PatchNotesRelationManager (패치노트 관계 매니저)

## 반응형 웹 디자인 (Responsive Web Design)

### 주요 브레이크포인트
- **모바일**: 599px 이하 (일반 스마트폰)
- **태블릿**: 600px ~ 1024px (갤럭시 폴드, 아이패드 등)
- **PC/데스크톱**: 1025px 이상

### 프론트엔드 작업 시 필수 체크사항
**중요**: 프론트엔드 관련 작업(CSS, HTML, UI 변경 등)을 할 때는 반드시 다음 세 가지 환경을 모두 고려해야 합니다:

1. **모바일 환경 (599px 이하)**
   - 햄버거 메뉴 표시 여부
   - 테이블 가로스크롤 작동
   - `hide-on-mobile` 클래스 적용 확인 (599px 이하)
   - 터치 인터랙션 (툴팁, 버튼 등)
   - 좁은 화면에서의 레이아웃
   - 일반 스마트폰 최적화

2. **태블릿 환경 (600px ~ 1024px)**
   - 햄버거 메뉴 표시 여부
   - 테이블 레이아웃
   - `hide-on-tablet` 클래스 적용 확인 (1024px 이하)
   - 중간 크기 화면에서의 레이아웃
   - 갤럭시 폴드, 아이패드 등 폴더블/태블릿 최적화

3. **PC/데스크톱 환경 (1025px 이상)**
   - 일반 네비게이션 메뉴 표시
   - 햄버거 메뉴 숨김 처리
   - 전체 테이블 컬럼 표시
   - 넓은 화면에서의 레이아웃

### 프론트엔드 수정 시 체크리스트
- [ ] 모바일에서 정상 작동하는가?
- [ ] 태블릿에서 정상 작동하는가?
- [ ] PC에서 정상 작동하는가?
- [ ] 각 환경에서 숨겨져야 할 요소들이 제대로 숨겨지는가?
- [ ] 각 환경에서 보여져야 할 요소들이 제대로 보이는가?
- [ ] 가로스크롤이 필요한 경우 올바르게 작동하는가?

## 제약사항
1. **터미널 명령어 실행 제한**: 모든 터미널 명령어는 사용자에게 먼저 요청하여 확인 받아야 함
2. **Filament 버전 주의**: 반드시 Filament 4 문법 사용
3. **반응형 디자인 필수**: 프론트엔드 작업 시 모바일/태블릿/PC 환경 모두 체크 필수

## 스케줄러 설정 (app/Console/Kernel.php)

### 명령어 실행 주기
| 명령어 | 실행 주기 | 실행 시간 | 설명 |
|--------|-----------|-----------|------|
| `fetch:game-results` | 매분 | - | 게임 결과 수집 |
| `update:game-results-summary` | 1시간마다 | 매시 0분 | 메인페이지 데이터 |
| `update:game-results-tactical-skill-summary` | 2시간마다 | 짝수시 10분 | 전술스킬 데이터 |
| `update:game-results-equipment-main-summary` | 2시간마다 | 짝수시 20분 | 장비 메인 데이터 |
| `update:game-results-first-equipment-main-summary` | 2시간마다 | 짝수시 30분 | 초반 장비 데이터 |
| `update:game-results-rank-summary` | 2시간마다 | 짝수시 40분 | 캐릭터별/순위별 데이터 |
| `update:game-results-trait-summary` | 2시간마다 | 짝수시 50분 | 캐릭터별/특성/순위별 데이터 |
| `update:game-results-equipment-summary` | 2시간마다 | 홀수시 10분 | 캐릭터별/장비별 데이터 |
| `update:game-result-trait-combination-summary` | 2시간마다 | 홀수시 30분 | 캐릭터별/특성조합별 데이터 |

**중요**: 모든 명령어는 최소 10분 이상 간격을 두고 실행되도록 설정. `withoutOverlapping()` 및 `runInBackground()` 옵션 사용.

## 환경 변수 (.env)

### 메인페이지/캐릭터페이지 기준 티어
```
ER_STAT_DEFALT_TIER=Diamond       # 캐릭터 페이지 기본 티어
ER_STAT_MAIN_PAGE_TIER=Meteorite  # 메인 페이지 기본 티어
```

## 최근 변경사항 (2025-11-28)

### 1. 특성 조합 통계 기능 추가
- **테이블**: `game_results_trait_combination_summary`
- **모델**: `GameResultTraitCombinationSummary`
- **서비스**: `GameResultTraitCombinationSummaryService`
- **명령어**: `update:game-result-trait-combination-summary`
- **API**: `/api/detail/{types}/trait-combinations`
- **특징**: 캐릭터별 선택한 특성 조합에 따른 통계 (GROUP_CONCAT으로 trait_ids 정렬 저장)

### 2. 상세페이지 특성 통계 UI 개선
- 특성 조합 통계 + 특성 개별 통계를 탭 메뉴로 통합
- 기본 탭: 특성 조합 통계 (상위 12개만 표시)
- 두 번째 탭: 특성 개별 통계 (전체 표시, 스크롤 영역 max-height: 500px)
- 아이콘 정렬: 메인 특성 → 같은 카테고리 서브 특성 → 나머지 서브 특성
- 메인 특성: 큰 아이콘 (44px) + 금색 테두리
- 서브 특성: 작은 아이콘 (32px)
- 모바일에서도 평균획득점수 표시

### 3. 게임 결과 저장 시 null 처리
- `mmr_before`가 null이면 0으로 저장
- `mmr_after`가 null이면 `mmr_before + mmr_gain`으로 계산하여 저장
- 위치: `GameResultService::storeGameResult()`

### 4. 인덱스 최적화
- `game_result_trait_orders` 테이블에 인덱스 추가
  - `trait_orders_game_result_id_idx` (game_result_id)
  - `trait_orders_game_result_trait_idx` (game_result_id, trait_id)
- 버전별 테이블에도 동일 인덱스 적용 (`idx_grt_game_result_trait`)

### 5. 버그 수정
- 특성 조합 통계 `positive_avg_mmr_gain`, `negative_avg_mmr_gain` 0으로 저장되는 버그
  - 원인: SQL alias와 PHP 변수명 불일치 (`avg_positive_mmr_gain` vs `positive_avg_mmr_gain`)
  - 수정: `GameResultService::getGameResultByTraitCombination()` 내 변수명 수정
