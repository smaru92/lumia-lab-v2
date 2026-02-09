# 아글라이아 연구소 (Lumia Lab V2)

> https://aglaia.dev

**이터널 리턴(Eternal Return)** 게임의 실시간 통계 분석 플랫폼입니다.
공식 API를 통해 게임 데이터를 수집하고, 캐릭터별 승률/픽률/장비/특성 등의 통계를 분석하여 제공합니다.

## 주요 기능

### 통계 대시보드
- **메인 페이지**: 패치별 캐릭터 버프/너프 비교 분석
- **캐릭터 통계**: 티어별 캐릭터 승률, 픽률, 평균 순위 제공
- **캐릭터 상세**: 캐릭터별 장비 빌드, 특성, 특성 조합, 전술 스킬 통계
- **장비 통계**: 완성 장비별 승률 및 사용률 분석
- **초반 장비 통계**: 게임 초반 장비 빌드 패턴 분석
- **특성 통계**: 특성별 승률 및 사용률 분석

### 실시간 데이터 수집
- 공식 API 연동을 통한 자동 게임 데이터 수집 (매분 실행)
- 버전별/티어별 데이터 분리 관리
- 주기적 통계 요약 데이터 갱신 (1~2시간 주기)

### 관리자 기능
- React SPA 기반 어드민 패널
- 캐릭터/장비/버전/패치노트 관리
- 캐릭터 태그 분류, 장비 스킬 관리
- Laravel Sanctum 인증

## 기술 스택

### Backend
- **Framework**: Laravel 12
- **PHP**: 8.2+
- **Database**: MySQL
- **Authentication**: Laravel Sanctum
- **API Client**: Guzzle HTTP

### Frontend (공개 페이지)
- **Template Engine**: Blade
- **Styling**: CSS (반응형 디자인)
- **JavaScript**: Vanilla JS + AJAX

### Frontend (어드민 패널)
- **UI Library**: React 18 + TypeScript
- **Build Tool**: Vite 6
- **Styling**: TailwindCSS 4
- **상태 관리**: TanStack React Query
- **테이블**: TanStack React Table
- **폼**: React Hook Form + Zod
- **UI 컴포넌트**: Radix UI
- **아이콘**: Lucide React
- **HTTP Client**: Axios
- **라우팅**: React Router DOM

### Infrastructure
- **Caching**: Database (설정 가능)
- **Queue**: Database (설정 가능)
- **Scheduling**: Laravel Task Scheduling

## 프로젝트 구조

```
lumia-lab-v2/
├── app/
│   ├── Console/Commands/           # Artisan 커맨드 (14개: 데이터 수집/집계)
│   ├── Http/
│   │   ├── Controllers/            # 웹 컨트롤러 (8개)
│   │   ├── Controllers/Admin/      # 어드민 API 컨트롤러 (9개)
│   │   ├── Middleware/             # 미들웨어
│   │   └── Resources/Admin/       # API 리소스 (5개)
│   ├── Models/                     # Eloquent 모델 (25개)
│   ├── Services/                   # 비즈니스 로직 서비스 (21개)
│   ├── Traits/                     # 공통 트레이트
│   └── Providers/                  # 서비스 프로바이더
├── config/
│   └── erDev.php                   # 게임 데이터 설정
├── database/
│   └── migrations/                 # DB 마이그레이션 (53개)
├── resources/views/                # Blade 템플릿 (공개 페이지)
├── resources/js/                   # React 앱 (어드민 패널)
└── routes/
    ├── web.php                     # 웹 라우트
    └── api.php                     # API 라우트 (40+ 엔드포인트)
```

## 데이터베이스 설계

### 핵심 테이블
| 테이블 | 설명 |
|--------|------|
| `game_results` | 게임 결과 원본 데이터 |
| `characters` | 캐릭터 기본 정보 및 스탯 |
| `equipments` | 장비 아이템 정보 |
| `traits` | 특성 정보 |
| `tactical_skills` | 전술 스킬 정보 |
| `version_history` | 게임 버전 이력 |
| `patch_notes` | 패치노트 내용 |
| `character_tags` | 캐릭터 분류 태그 |
| `equipment_skills` | 장비 스킬 연결 정보 |

### 게임 결과 상세 테이블
| 테이블 | 설명 |
|--------|------|
| `game_result_skill_orders` | 스킬 선택 순서 |
| `game_result_trait_orders` | 특성 선택 정보 |
| `game_result_equipment_orders` | 장비 아이템 순서 |
| `game_result_first_equipment_orders` | 초반 장비 정보 |

### 통계 요약 테이블
| 테이블 | 설명 |
|--------|------|
| `game_results_summary` | 캐릭터별 전체 통계 요약 |
| `game_results_rank_summary` | 순위별 통계 요약 |
| `game_results_equipment_summary` | 장비별 통계 요약 |
| `game_results_equipment_main_summary` | 메인 장비 통계 요약 |
| `game_results_first_equipment_main_summary` | 초반 메인 장비 통계 요약 |
| `game_results_trait_summary` | 특성별 통계 요약 |
| `game_results_trait_main_summary` | 특성 메인 통계 요약 |
| `game_results_trait_combination_summary` | 특성 조합별 통계 요약 |
| `game_results_tactical_skill_summary` | 전술 스킬별 통계 요약 |

## 주요 Artisan 명령어

### 데이터 수집
```bash
# 게임 결과 데이터 수집
php artisan fetch:game-results

# 캐릭터 정보 동기화
php artisan app:fetch-characters

# 장비 정보 동기화
php artisan app:fetch-equipments
```

### 통계 요약 갱신
```bash
# 메인 페이지 요약
php artisan update:game-results-summary

# 순위별 요약
php artisan update:game-results-rank-summary

# 장비 관련 요약
php artisan update:game-results-equipment-summary
php artisan update:game-results-equipment-main-summary
php artisan update:game-results-first-equipment-main-summary

# 특성 관련 요약
php artisan update:game-results-trait-summary
php artisan update:game-results-trait-main-summary
php artisan update:game-result-trait-combination-summary

# 전술 스킬 요약
php artisan update:game-results-tactical-skill-summary
```

모든 요약 명령어는 버전 인자를 지원합니다: `{version_season?} {version_major?} {version_minor?}`

### 유틸리티
```bash
# 이미지 리사이즈
php artisan images:resize --width=80
```

## 스케줄러

| 명령어 | 실행 주기 | 설명 |
|--------|-----------|------|
| `fetch:game-results` | 매분 | 게임 결과 수집 |
| `update:game-results-summary` | 1시간마다 (매시 0분) | 메인페이지 데이터 |
| `update:game-results-tactical-skill-summary` | 2시간마다 (짝수시 10분) | 전술스킬 데이터 |
| `update:game-results-equipment-main-summary` | 2시간마다 (짝수시 20분) | 장비 메인 데이터 |
| `update:game-results-first-equipment-main-summary` | 2시간마다 (짝수시 30분) | 초반 장비 데이터 |
| `update:game-results-rank-summary` | 2시간마다 (짝수시 40분) | 순위별 데이터 |
| `update:game-results-trait-summary` | 2시간마다 (짝수시 50분) | 특성별 데이터 |
| `update:game-results-equipment-summary` | 2시간마다 (홀수시 10분) | 장비별 데이터 |
| `update:game-result-trait-combination-summary` | 2시간마다 (홀수시 30분) | 특성 조합 데이터 |
| `update:game-results-trait-main-summary` | 2시간마다 (홀수시 50분) | 특성 메인 데이터 |

모든 명령어는 `withoutOverlapping()` 및 `runInBackground()` 옵션 사용.

## 설치 및 실행

### 요구사항
- PHP 8.2 이상
- Composer
- MySQL 8.0 이상
- Node.js (프론트엔드 빌드용)

### 설치

```bash
# 의존성 설치
composer install
npm install

# 환경 설정
cp .env.example .env
php artisan key:generate

# 데이터베이스 마이그레이션
php artisan migrate

# 초기 데이터 시딩
php artisan db:seed

# 프론트엔드 빌드
npm run build
```

### 환경 변수 설정

```env
# 이터널 리턴 API 설정
ER_API_KEY=your_api_key

# 통계 기본 설정
ER_STAT_DEFALT_VERSION=10.0.0
ER_STAT_DEFALT_TIER=Diamond
ER_STAT_MAIN_PAGE_TIER=Diamond
ER_STAT_TOP_RANK_SCORE=8000

# 데이터 수집 설정
ER_FETCH_GAME_UNIT_NUMBER=30
ER_SEARCH_GAME_NUMBER=5

# 이미지 캐시 버스팅
IMAGE_VERSION=v2

# 캐시 설정 (초 단위)
CACHE_DURATION=1800
```

### 실행

```bash
# 개발 서버 실행
composer dev

# 또는 개별 실행
php artisan serve
npm run dev
```

## 페이지 및 API

### 웹 페이지
| 경로 | 설명 |
|------|------|
| `/` | 메인 페이지 (패치 비교) |
| `/character` | 캐릭터 통계 목록 |
| `/detail/{character}` | 캐릭터 상세 통계 |
| `/equipment` | 완성 장비 통계 |
| `/equipment-first` | 초반 장비 통계 |
| `/trait` | 특성 통계 |
| `/admin` | 관리자 패널 (React SPA) |

### 공개 API
| 경로 | 설명 |
|------|------|
| `GET /api/character` | 캐릭터 정보 조회 |
| `GET /api/equipment` | 장비 정보 조회 |
| `GET /api/item` | 아이템 정보 조회 |
| `GET /api/skill` | 스킬 정보 조회 |
| `GET /api/trait` | 특성 정보 조회 |
| `GET /api/patch-comparison` | 패치 비교 데이터 |
| `GET /api/detail/{types}/tiers` | 캐릭터 티어 통계 |
| `GET /api/detail/{types}/ranks` | 캐릭터 순위 통계 |
| `GET /api/detail/{types}/equipment` | 캐릭터 장비 통계 |
| `GET /api/detail/{types}/traits` | 캐릭터 특성 통계 |
| `GET /api/detail/{types}/trait-combinations` | 캐릭터 특성 조합 통계 |
| `GET /api/detail/{types}/tactical-skills` | 캐릭터 전술 스킬 통계 |

### 관리자 API (인증 필요)
| 경로 | 설명 |
|------|------|
| `POST /api/admin/login` | 로그인 |
| `POST /api/admin/logout` | 로그아웃 |
| `/api/admin/characters` | 캐릭터 관리 (조회/수정) |
| `/api/admin/character-tags` | 캐릭터 태그 관리 (CRUD) |
| `/api/admin/equipment` | 장비 관리 (조회/수정) |
| `/api/admin/equipment-skills` | 장비 스킬 관리 (CRUD) |
| `/api/admin/version-histories` | 버전 이력 관리 (CRUD) |
| `/api/admin/version-histories/{id}/patch-notes` | 패치노트 관리 (CRUD) |
| `/api/admin/traits` | 특성 조회 (폼 옵션용) |
| `/api/admin/tactical-skills` | 전술 스킬 조회 (폼 옵션용) |

## 성능 최적화

- **캐싱**: 통계 데이터 30분 캐싱으로 DB 부하 감소
- **요약 테이블**: 사전 집계된 통계 테이블로 실시간 쿼리 최소화
- **Lazy Loading**: 상세 페이지 AJAX 기반 점진적 로딩
- **인덱스 최적화**: 버전/티어/캐릭터 복합 인덱스 적용
- **비동기 스케줄링**: 모든 집계 명령어 10분 이상 간격, 중복 실행 방지

## 반응형 디자인

- **모바일** (599px 이하): 햄버거 메뉴, 가로 스크롤 테이블
- **태블릿** (600px ~ 1024px): 적응형 레이아웃
- **데스크톱** (1025px 이상): 전체 기능 표시

## 라이선스

MIT License
