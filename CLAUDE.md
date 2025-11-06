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
- **game_results_trait_summary**: 특성 요약
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
- **모바일**: 768px 이하
- **태블릿**: 769px ~ 1024px
- **PC/데스크톱**: 1025px 이상

### 프론트엔드 작업 시 필수 체크사항
**중요**: 프론트엔드 관련 작업(CSS, HTML, UI 변경 등)을 할 때는 반드시 다음 세 가지 환경을 모두 고려해야 합니다:

1. **모바일 환경 (768px 이하)**
   - 햄버거 메뉴 표시 여부
   - 테이블 가로스크롤 작동
   - `hide-on-mobile` 클래스 적용 확인
   - 터치 인터랙션 (툴팁, 버튼 등)
   - 좁은 화면에서의 레이아웃

2. **태블릿 환경 (769px ~ 1024px)**
   - 햄버거 메뉴 표시 여부
   - 테이블 레이아웃
   - `hide-on-tablet` 클래스 적용 확인
   - 중간 크기 화면에서의 레이아웃

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
