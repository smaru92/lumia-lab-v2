import { Fragment, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '@/lib/axios';
import { PageHeader } from '@/components/shared/PageHeader';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface Metrics {
    game_count: number;
    game_count_percent: number;
    meta_score: number;
    meta_tier: string | null;
    top1_count_percent: number;
    top4_count_percent: number;
    avg_mmr_gain: number;
    avg_team_kill_score: number;
}

type MetricKey = keyof Omit<Metrics, 'meta_tier'>;

interface ComparisonRow {
    key: string;
    character_id: number;
    character_name: string;
    weapon_type: string | null;
    weapon_type_ko: string;
    character_image: string;
    weapon_image: string | null;
    tags: string[];
    current: Metrics;
    previous: Metrics | null;
    diff: Record<MetricKey, number> | null;
    is_new: boolean;
    patch_flag: 'buff' | 'nerf' | 'mixed' | 'other' | null;
    patch_types: string[];
    patch_contents: string[];
}

interface Options {
    versions: { value: string; label: string }[];
    tiers: string[];
    default_tier: string;
    characters: { value: string; label: string }[];
    group_by: { value: string; label: string }[];
    patch_filters: { value: string; label: string }[];
    trend_filters: { value: string; label: string }[];
    tags: { value: string; label: string }[];
}

interface Meta {
    version: string | null;
    base_version: string | null;
    target_table: string | null;
    base_table: string | null;
    exists: boolean;
    count: number;
    dropped_count: number;
    weapon_types: { value: string; label: string }[];
}

const TIER_LABELS: Record<string, string> = {
    All: '전체',
    Platinum: '플래티넘',
    Diamond: '다이아',
    Diamond2: '다이아2',
    Meteorite: '메테오라이트',
    Mithrillow: '미스릴',
    Mithrilhigh: '미스릴(8000+)',
    Top: '최상위큐',
};

const PATCH_TYPE_COLORS: Record<string, 'buff' | 'nerf' | 'warning' | 'info' | 'secondary' | 'success' | 'destructive'> = {
    버프: 'buff',
    너프: 'nerf',
    조정: 'warning',
    리워크: 'info',
    신규: 'success',
    삭제: 'destructive',
};

/** 표에 보여줄 지표 (정렬 키 = 서버의 diff 키) */
const COLUMNS: { key: MetricKey; label: string; digits: number; suffix?: string }[] = [
    { key: 'meta_score', label: '메타점수', digits: 2 },
    { key: 'game_count_percent', label: '픽률', digits: 2, suffix: '%' },
    { key: 'top1_count_percent', label: '승률', digits: 2, suffix: '%' },
    { key: 'top4_count_percent', label: 'TOP4율', digits: 2, suffix: '%' },
    { key: 'avg_mmr_gain', label: '평균획득', digits: 2 },
    { key: 'avg_team_kill_score', label: '평균TK', digits: 2 },
    { key: 'game_count', label: '게임수', digits: 0 },
];

const num = (v: number, digits = 1) =>
    v.toLocaleString(undefined, { minimumFractionDigits: digits, maximumFractionDigits: digits });

/** 변동값 색 (오른 쪽은 초록, 내린 쪽은 빨강) */
const diffClass = (v: number) =>
    v > 0 ? 'text-emerald-600' : v < 0 ? 'text-rose-600' : 'text-[hsl(var(--muted-foreground))]';

const signed = (v: number, digits: number) => (v > 0 ? '+' : '') + num(v, digits);

/** 메타 티어 배경색 — 메인 페이지(public/css/main.css .tier-*)와 동일하게 맞춘다 */
const TIER_COLORS: Record<string, string> = {
    OP: '#8A2BE2',
    '1': '#B22222',
    '2': '#FF8C00',
    '3': '#FFD700',
    '4': '#a8e16f',
    '5': '#7fbfff',
    RIP: '#696969',
};
const TIER_UNKNOWN = '#cccccc';

/** 티어 높은 순 (배열 앞이 상위). 상승/하락 방향 판단에 쓴다 */
const TIER_ORDER = ['OP', '1', '2', '3', '4', '5', 'RIP'];

const tierMoved = (r: ComparisonRow) =>
    !!r.previous?.meta_tier && !!r.current.meta_tier && r.previous.meta_tier !== r.current.meta_tier;

/** 인덱스가 작을수록 상위 티어라서 이전 - 현재 가 양수면 상승 */
const tierMoveDelta = (r: ComparisonRow) =>
    TIER_ORDER.indexOf(r.previous?.meta_tier ?? '') - TIER_ORDER.indexOf(r.current.meta_tier ?? '');

const tierMoveArrow = (r: ComparisonRow) => (tierMoveDelta(r) > 0 ? '▲' : '▼');

const tierMoveClass = (r: ComparisonRow) =>
    tierMoveDelta(r) > 0 ? 'text-xs text-emerald-600' : 'text-xs text-rose-600';

/** 메인 페이지의 .tier-badge 와 같은 모양 */
function TierBadge({ tier, faded = false }: { tier: string | null; faded?: boolean }) {
    if (!tier) {
        return <span className="text-[hsl(var(--muted-foreground))]">-</span>;
    }

    const background = TIER_COLORS[tier] ?? TIER_UNKNOWN;

    return (
        <span
            className="inline-block min-w-[30px] rounded px-2 py-0.5 text-center text-xs font-bold leading-tight"
            style={{
                backgroundColor: background,
                color: TIER_COLORS[tier] ? '#ffffff' : '#333333',
                opacity: faded ? 0.55 : 1,
            }}
        >
            {tier}
        </span>
    );
}

/**
 * 캐릭터 아이콘 + 무기 아이콘 오버레이
 * 메인 페이지 .icon-container / .character-icon / .weapon-icon 과 동일한 구성
 * (아이콘 원본이 정사각형이 아니라 object-fit: cover 로 잘라서 채운다)
 */
function CharacterIcon({ row }: { row: ComparisonRow }) {
    return (
        <div className="relative h-[45px] w-[45px] shrink-0 overflow-hidden rounded">
            <img
                src={row.character_image}
                alt={row.character_name}
                className="h-full w-full object-cover"
                loading="lazy"
                onError={(e) => {
                    const img = e.currentTarget as HTMLImageElement;
                    img.onerror = null;
                    img.src = '/storage/Character/icon/default.png';
                }}
            />
            {row.weapon_image && (
                <img
                    src={row.weapon_image}
                    alt={row.weapon_type_ko}
                    className="absolute bottom-px right-px h-4 w-4 rounded-[3px] bg-[#333] p-px"
                    loading="lazy"
                    // 무기 기본 아이콘은 실제로 없어서(메인 페이지도 마찬가지) 실패하면 그냥 숨긴다
                    onError={(e) => { (e.currentTarget as HTMLImageElement).style.display = 'none'; }}
                />
            )}
        </div>
    );
}

/** 세그먼트 토글 (패치 여부 / 티어 점수 추세) */
function ToggleGroup({
    value,
    onChange,
    items,
}: {
    value: string;
    onChange: (v: string) => void;
    items: { value: string; label: string }[];
}) {
    return (
        <div className="inline-flex flex-wrap rounded-md border border-[hsl(var(--border))] p-1">
            {items.map((item) => (
                <button
                    key={item.value}
                    type="button"
                    onClick={() => onChange(item.value)}
                    className={cn(
                        'rounded px-3 py-1.5 text-sm font-medium transition-colors',
                        value === item.value
                            ? 'bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))]'
                            : 'text-[hsl(var(--muted-foreground))] hover:bg-[hsl(var(--accent))] hover:text-[hsl(var(--accent-foreground))]'
                    )}
                >
                    {item.label}
                </button>
            ))}
        </div>
    );
}

export default function VersionComparisonPage() {
    const [version, setVersion] = useState('');
    const [baseVersion, setBaseVersion] = useState('');
    const [minTier, setMinTier] = useState('');
    const [groupBy, setGroupBy] = useState('weapon');
    const [characterId, setCharacterId] = useState('');
    const [weaponType, setWeaponType] = useState('');
    const [patchFilter, setPatchFilter] = useState('all');
    const [trend, setTrend] = useState('all');
    const [tag, setTag] = useState('');
    const [search, setSearch] = useState('');
    const [minGameCount, setMinGameCount] = useState('30');
    const [sort, setSort] = useState<MetricKey>('meta_score');
    const [direction, setDirection] = useState<'asc' | 'desc'>('desc');
    const [expanded, setExpanded] = useState<string | null>(null);

    const { data: options } = useQuery<Options>({
        queryKey: ['version-comparison', 'options'],
        queryFn: async () => (await api.get('/version-comparison/options')).data.data,
    });

    const params = {
        version: version || undefined,
        base_version: baseVersion || undefined,
        min_tier: minTier || undefined,
        group_by: groupBy,
        character_id: characterId || undefined,
        weapon_type: weaponType || undefined,
        patch_filter: patchFilter,
        trend,
        tag: tag || undefined,
        search: search || undefined,
        min_game_count: minGameCount || undefined,
        sort,
        direction,
    };

    const { data, isFetching } = useQuery<{ data: ComparisonRow[]; meta: Meta }>({
        queryKey: ['version-comparison', params],
        queryFn: async () => (await api.get('/version-comparison', { params })).data,
    });

    const rows = data?.data ?? [];
    const meta = data?.meta;
    const weaponOptions = meta?.weapon_types ?? [];
    const byWeapon = groupBy === 'weapon';
    // 캐릭터 / 티어 / 패치 + 지표별 (현재, 변동)
    const colCount = 3 + COLUMNS.length * 2;

    const toggleSort = (key: MetricKey) => {
        if (sort === key) {
            setDirection((d) => (d === 'desc' ? 'asc' : 'desc'));
        } else {
            setSort(key);
            setDirection('desc');
        }
    };

    return (
        <div className="space-y-6">
            <PageHeader
                title="전체 지표 변동"
                description="두 버전의 캐릭터 지표를 비교합니다. 패치노트에 없는 캐릭터도 모두 포함되므로 간접 버프/너프를 확인할 수 있습니다."
            />

            <Card>
                <CardContent className="grid gap-4 pt-6 md:grid-cols-3 lg:grid-cols-5">
                    <div className="space-y-2">
                        <Label>비교 버전 (현재)</Label>
                        <Select value={version || '__latest__'} onValueChange={(v) => setVersion(v === '__latest__' ? '' : v)}>
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__latest__">최신 버전</SelectItem>
                                {(options?.versions ?? []).map((v) => (
                                    <SelectItem key={v.value} value={v.value}>{v.label}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-2">
                        <Label>기준 버전 (이전)</Label>
                        <Select value={baseVersion || '__prev__'} onValueChange={(v) => setBaseVersion(v === '__prev__' ? '' : v)}>
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__prev__">직전 버전</SelectItem>
                                {(options?.versions ?? []).map((v) => (
                                    <SelectItem key={v.value} value={v.value}>{v.label}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-2">
                        <Label>최소 티어</Label>
                        <Select value={minTier || '__default__'} onValueChange={(v) => setMinTier(v === '__default__' ? '' : v)}>
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__default__">
                                    기본 티어{options?.default_tier ? ` (${TIER_LABELS[options.default_tier] ?? options.default_tier})` : ''}
                                </SelectItem>
                                {(options?.tiers ?? []).map((t) => (
                                    <SelectItem key={t} value={t}>{TIER_LABELS[t] ?? t}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-2">
                        <Label>집계 단위</Label>
                        <Select value={groupBy} onValueChange={(v) => { setGroupBy(v); setWeaponType(''); }}>
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                {(options?.group_by ?? []).map((g) => (
                                    <SelectItem key={g.value} value={g.value}>{g.label}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-2">
                        <Label>캐릭터</Label>
                        <Select value={characterId || '__all__'} onValueChange={(v) => { setCharacterId(v === '__all__' ? '' : v); setWeaponType(''); }}>
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__all__">전체</SelectItem>
                                {(options?.characters ?? []).map((c) => (
                                    <SelectItem key={c.value} value={c.value}>{c.label}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    {byWeapon && (
                        <div className="space-y-2">
                            <Label>무기군</Label>
                            <Select
                                value={weaponType || '__all__'}
                                onValueChange={(v) => setWeaponType(v === '__all__' ? '' : v)}
                                disabled={weaponOptions.length === 0}
                            >
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="__all__">전체</SelectItem>
                                    {weaponOptions.map((w) => (
                                        <SelectItem key={w.value} value={w.value}>{w.label}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    )}

                    <div className="space-y-2">
                        <Label>태그</Label>
                        <Select value={tag || '__all__'} onValueChange={(v) => setTag(v === '__all__' ? '' : v)}>
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__all__">전체</SelectItem>
                                {(options?.tags ?? []).map((t) => (
                                    <SelectItem key={t.value} value={t.value}>{t.label}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="search">캐릭터 검색</Label>
                        <Input id="search" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="예: 재키" />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="minGameCount">최소 게임수 (현재 버전)</Label>
                        <Input
                            id="minGameCount"
                            type="number"
                            min={0}
                            value={minGameCount}
                            onChange={(e) => setMinGameCount(e.target.value)}
                        />
                    </div>
                </CardContent>
            </Card>

            {/* 메인 페이지는 패치 대상만 보여준다. 여기서는 토글로 전체/패치 대상을 오간다. */}
            <div className="flex flex-wrap items-center gap-x-6 gap-y-3">
                <div className="flex items-center gap-2">
                    <span className="text-sm text-[hsl(var(--muted-foreground))]">패치</span>
                    <ToggleGroup
                        value={patchFilter}
                        onChange={setPatchFilter}
                        items={options?.patch_filters ?? []}
                    />
                </div>
                <div className="flex items-center gap-2">
                    <span className="text-sm text-[hsl(var(--muted-foreground))]">티어 점수</span>
                    <ToggleGroup
                        value={trend}
                        onChange={setTrend}
                        items={options?.trend_filters ?? []}
                    />
                </div>
            </div>

            <div className="flex flex-wrap items-center justify-between gap-2 text-sm text-[hsl(var(--muted-foreground))]">
                <span>
                    {isFetching ? '조회 중...' : `${rows.length.toLocaleString()}건`}
                    {meta?.version && meta?.base_version && (
                        <span className="ml-2 font-medium text-[hsl(var(--foreground))]">
                            {meta.base_version} → {meta.version}
                        </span>
                    )}
                    {meta?.exists === false && ' — 두 버전 중 집계 테이블이 없는 버전이 있습니다'}
                    {!!meta?.dropped_count && ` · 이번 버전에서 사라진 조합 ${meta.dropped_count}건 제외`}
                </span>
                {meta?.target_table && (
                    <span className="font-mono text-xs">{meta.base_table} vs {meta.target_table}</span>
                )}
            </div>

            {!byWeapon && (
                <p className="text-xs text-[hsl(var(--muted-foreground))]">
                    캐릭터 합산은 게임수 가중평균입니다. 픽률·게임수는 무기별 합계, 나머지는 가중평균이며 메타점수는 근사치입니다.
                </p>
            )}

            <div className="overflow-x-auto rounded-md border border-[hsl(var(--border))]">
                <table className="w-full min-w-[1200px] text-sm">
                    <thead>
                        <tr className="border-b border-[hsl(var(--border))] bg-[hsl(var(--muted))]">
                            <th rowSpan={2} className="px-3 py-2 text-left">캐릭터</th>
                            <th rowSpan={2} className="px-3 py-2 text-left">티어</th>
                            <th rowSpan={2} className="px-3 py-2 text-left">패치</th>
                            {COLUMNS.map((c) => (
                                <th
                                    key={c.key}
                                    colSpan={2}
                                    className="cursor-pointer select-none border-l border-[hsl(var(--border))] px-3 py-2 text-center hover:bg-[hsl(var(--accent))]"
                                    onClick={() => toggleSort(c.key)}
                                    title="클릭하면 변동폭 기준으로 정렬합니다"
                                >
                                    {c.label}
                                    {sort === c.key && <span className="ml-1">{direction === 'desc' ? '▼' : '▲'}</span>}
                                </th>
                            ))}
                        </tr>
                        <tr className="border-b border-[hsl(var(--border))] bg-[hsl(var(--muted))] text-xs">
                            {COLUMNS.map((c) => (
                                <Fragment key={c.key}>
                                    <th className="border-l border-[hsl(var(--border))] px-3 py-1 text-right">현재</th>
                                    <th className="px-3 py-1 text-right">변동</th>
                                </Fragment>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((r) => (
                            <Fragment key={r.key}>
                                <tr
                                    className="cursor-pointer border-b border-[hsl(var(--border))] hover:bg-[hsl(var(--muted))]"
                                    onClick={() => setExpanded(expanded === r.key ? null : r.key)}
                                >
                                    <td className="px-3 py-2">
                                        <div className="flex items-center gap-2">
                                            <CharacterIcon row={r} />
                                            <div className="min-w-0">
                                                <div className="flex items-center gap-1.5">
                                                    <span>{r.character_name}</span>
                                                    {r.is_new && (
                                                        <span className="rounded bg-[hsl(var(--accent))] px-1.5 py-0.5 text-xs">신규</span>
                                                    )}
                                                </div>
                                                {byWeapon && (
                                                    <div className="text-xs text-[hsl(var(--muted-foreground))]">
                                                        {r.weapon_type_ko}
                                                    </div>
                                                )}
                                                {r.tags.length > 0 && (
                                                    <div className="text-xs text-[hsl(var(--muted-foreground))]">
                                                        {r.tags.join(' · ')}
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </td>
                                    <td className="whitespace-nowrap px-3 py-2">
                                        {tierMoved(r) ? (
                                            <span className="inline-flex items-center gap-1">
                                                <TierBadge tier={r.previous!.meta_tier} faded />
                                                <span className={tierMoveClass(r)}>{tierMoveArrow(r)}</span>
                                                <TierBadge tier={r.current.meta_tier} />
                                            </span>
                                        ) : (
                                            <TierBadge tier={r.current.meta_tier} />
                                        )}
                                    </td>
                                    <td className="px-3 py-2">
                                        <div className="flex flex-wrap gap-1">
                                            {r.patch_types.length === 0 ? (
                                                <span className="text-xs text-[hsl(var(--muted-foreground))]">-</span>
                                            ) : (
                                                r.patch_types.map((t) => (
                                                    <Badge key={t} variant={PATCH_TYPE_COLORS[t] || 'secondary'}>{t}</Badge>
                                                ))
                                            )}
                                        </div>
                                    </td>
                                    {COLUMNS.map((c) => (
                                        <Fragment key={c.key}>
                                            <td className="border-l border-[hsl(var(--border))] px-3 py-2 text-right">
                                                {num(r.current[c.key], c.digits)}{c.suffix ?? ''}
                                            </td>
                                            <td className={`px-3 py-2 text-right font-medium ${r.diff ? diffClass(r.diff[c.key]) : ''}`}>
                                                {r.diff ? `${signed(r.diff[c.key], c.digits)}${c.suffix ?? ''}` : '-'}
                                            </td>
                                        </Fragment>
                                    ))}
                                </tr>
                                {expanded === r.key && (
                                    <tr className="border-b border-[hsl(var(--border))] bg-[hsl(var(--muted))]">
                                        <td colSpan={colCount} className="px-3 py-3 text-xs">
                                            <div className="mb-2">
                                                <span className="font-semibold">이전 버전({meta?.base_version}) 값</span>
                                                {r.previous ? (
                                                    <span className="ml-2 text-[hsl(var(--muted-foreground))]">
                                                        {COLUMNS.map((c) => `${c.label} ${num(r.previous![c.key], c.digits)}${c.suffix ?? ''}`).join(' · ')}
                                                    </span>
                                                ) : (
                                                    <span className="ml-2 text-[hsl(var(--muted-foreground))]">데이터 없음</span>
                                                )}
                                            </div>
                                            {r.patch_contents.length > 0 && (
                                                <div>
                                                    <span className="font-semibold">패치 내용</span>
                                                    <ul className="ml-4 mt-1 list-disc space-y-0.5 text-[hsl(var(--muted-foreground))]">
                                                        {r.patch_contents.map((content, i) => (
                                                            <li key={i}>{content}</li>
                                                        ))}
                                                    </ul>
                                                </div>
                                            )}
                                        </td>
                                    </tr>
                                )}
                            </Fragment>
                        ))}
                        {rows.length === 0 && !isFetching && (
                            <tr>
                                <td colSpan={colCount} className="px-3 py-10 text-center text-[hsl(var(--muted-foreground))]">
                                    조건에 해당하는 데이터가 없습니다.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
