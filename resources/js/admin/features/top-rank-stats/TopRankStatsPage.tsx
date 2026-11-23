import { Fragment, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '@/lib/axios';
import { PageHeader } from '@/components/shared/PageHeader';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface Metrics {
    game_count: number;
    avg_mmr_gain: number;
    avg_team_kill: number;
    positive_percent: number;
    positive_avg: number;
    negative_percent: number;
    negative_avg: number;
}

interface StatRow {
    item_id: number;
    item_name: string;
    image: string;
    group_code: string;
    group_label: string;
    sub_group_code: string;
    sub_group_label: string;
    character_id: number | null;
    character_name: string | null;
    weapon_type: string | null;
    top: Metrics;
    all: Metrics;
    top_rate: number;
    /** 전 캐릭터 합산일 때만 채워진다 (사용률 순) */
    top_characters: TopCharacter[];
}

interface TopCharacter {
    character_id: number;
    character_name: string;
    weapon_type: string;
    weapon_label: string;
    game_count: number;
    total_count: number;
    usage_rate: number;
    avg_mmr_gain: number;
    top_rate: number;
}

interface Options {
    types: { value: string; label: string }[];
    versions: { value: string; label: string }[];
    tiers: string[];
    characters: { value: string; label: string }[];
    groups: Record<string, { value: string; label: string }[]>;
    sub_groups: Record<string, { value: string; label: string }[]>;
    filter_labels: Record<string, { group: string; sub_group: string }>;
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

/** 주 사용 캐릭터를 접었을 때 보여주는 수 */
const COLLAPSED_CHARACTERS = 3;

const num = (v: number, digits = 1) =>
    v.toLocaleString(undefined, { minimumFractionDigits: digits, maximumFractionDigits: digits });

/** 값의 부호에 따라 색을 준다 (획득점수 계열) */
const signClass = (v: number) =>
    v > 0 ? 'text-emerald-600' : v < 0 ? 'text-rose-600' : '';

export default function TopRankStatsPage() {
    const [type, setType] = useState('trait');
    const [version, setVersion] = useState('');
    const [minTier, setMinTier] = useState('Diamond');
    const [characterId, setCharacterId] = useState('');
    const [group, setGroup] = useState('');
    const [subGroup, setSubGroup] = useState('');
    const [weaponType, setWeaponType] = useState('');
    const [search, setSearch] = useState('');
    const [minGameCount, setMinGameCount] = useState('10');
    const [minCharacterGameCount, setMinCharacterGameCount] = useState('10');
    const [expanded, setExpanded] = useState<Record<string, boolean>>({});

    const { data: options } = useQuery<Options>({
        queryKey: ['top-rank-stats', 'options'],
        queryFn: async () => (await api.get('/top-rank-stats/options')).data.data,
    });

    const params = {
        type,
        version: version || undefined,
        min_tier: minTier,
        character_id: characterId || undefined,
        group: group || undefined,
        sub_group: subGroup || undefined,
        weapon_type: weaponType || undefined,
        search: search || undefined,
        min_game_count: minGameCount || undefined,
        min_character_game_count: minCharacterGameCount || undefined,
    };

    const { data, isFetching } = useQuery<{
        data: StatRow[];
        meta: {
            count: number;
            exists: boolean;
            table: string;
            per_character: boolean;
            weapon_types: { value: string; label: string }[];
        };
    }>({
        queryKey: ['top-rank-stats', params],
        queryFn: async () => (await api.get('/top-rank-stats', { params })).data,
    });

    const groupOptions = options?.groups?.[type] ?? [];
    const subGroupOptions = options?.sub_groups?.[type] ?? [];
    const labels = options?.filter_labels?.[type] ?? { group: '그룹', sub_group: '분류' };
    // 무기군은 캐릭터마다 구성이 달라(알렉스는 합계 'All', 에키온은 전용 3종)
    // 서버가 현재 조건에서 실제 존재하는 값만 내려준다.
    const weaponOptions = data?.meta?.weapon_types ?? [];
    const weaponLabel = (code: string | null) =>
        weaponOptions.find((w) => w.value === code)?.label ?? code ?? '';

    // 캐릭터를 고르지 않으면 서버가 전 캐릭터를 합친 항목 단위로 내려준다.
    // 그때는 캐릭터/무기 열 대신 "주 사용 캐릭터" 열을 보여준다.
    const perCharacter = data?.meta?.per_character ?? !!characterId;
    const columnCount = perCharacter ? 14 : 13;
    // 종류마다 id 체계가 달라서(특성/아이템/전술스킬) 종류를 붙여 키로 쓴다
    const toggle = (key: string) => setExpanded((prev) => ({ ...prev, [key]: !prev[key] }));

    // 캐릭터 상세처럼 그룹 단위로 묶어서 보여준다 (그룹 없는 종류는 한 덩어리)
    const rows = data?.data ?? [];
    const groupOrder = groupOptions.map((g) => g.value);
    const grouped = rows.reduce<Record<string, StatRow[]>>((acc, row) => {
        const key = row.group_code || '';
        (acc[key] ||= []).push(row);
        return acc;
    }, {});
    const groupKeys = Object.keys(grouped).sort(
        (a, b) => (groupOrder.indexOf(a) + 1 || 99) - (groupOrder.indexOf(b) + 1 || 99)
    );

    return (
        <div className="space-y-6">
            <PageHeader
                title="TOP4 상세 지표"
                description="각 특성/아이템/전술스킬이 TOP4를 기록한 게임만 따로 집계합니다. 캐릭터를 고르지 않으면 전 캐릭터 합산, 고르면 그 캐릭터의 무기별로 보여줍니다."
            />

            <Card>
                <CardContent className="grid gap-4 pt-6 md:grid-cols-3 lg:grid-cols-6">
                    <div className="space-y-2">
                        <Label>종류</Label>
                        <Select value={type} onValueChange={(v) => { setType(v); setGroup(''); setSubGroup(''); }}>
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                {(options?.types ?? []).map((t) => (
                                    <SelectItem key={t.value} value={t.value}>{t.label}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-2">
                        <Label>버전</Label>
                        <Select value={version || '__default__'} onValueChange={(v) => setVersion(v === '__default__' ? '' : v)}>
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="__default__">기본 버전</SelectItem>
                                {(options?.versions ?? []).map((v) => (
                                    <SelectItem key={v.value} value={v.value}>{v.label}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-2">
                        <Label>최소 티어</Label>
                        <Select value={minTier} onValueChange={setMinTier}>
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                {(options?.tiers ?? []).map((t) => (
                                    <SelectItem key={t} value={t}>{TIER_LABELS[t] ?? t}</SelectItem>
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

                    {groupOptions.length > 0 && (
                        <div className="space-y-2">
                            <Label>{labels.group}</Label>
                            <Select value={group || '__all__'} onValueChange={(v) => setGroup(v === '__all__' ? '' : v)}>
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="__all__">전체</SelectItem>
                                    {groupOptions.map((g) => (
                                        <SelectItem key={g.value} value={g.value}>{g.label}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    )}

                    {subGroupOptions.length > 0 && (
                        <div className="space-y-2">
                            <Label>{labels.sub_group}</Label>
                            <Select value={subGroup || '__all__'} onValueChange={(v) => setSubGroup(v === '__all__' ? '' : v)}>
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="__all__">전체</SelectItem>
                                    {subGroupOptions.map((g) => (
                                        <SelectItem key={g.value} value={g.value}>{g.label}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    )}

                    <div className="space-y-2">
                        <Label htmlFor="search">이름 검색</Label>
                        <Input id="search" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="예: 곰 탈" />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="minGameCount">최소 TOP4 게임수</Label>
                        <Input
                            id="minGameCount"
                            type="number"
                            min={0}
                            value={minGameCount}
                            onChange={(e) => setMinGameCount(e.target.value)}
                        />
                    </div>

                    {!characterId && (
                        <div className="space-y-2">
                            <Label htmlFor="minCharacterGameCount">주 사용 캐릭터 최소 게임수</Label>
                            <Input
                                id="minCharacterGameCount"
                                type="number"
                                min={1}
                                value={minCharacterGameCount}
                                onChange={(e) => setMinCharacterGameCount(e.target.value)}
                            />
                        </div>
                    )}
                </CardContent>
            </Card>

            <div className="flex items-center justify-between text-sm text-[hsl(var(--muted-foreground))]">
                <span>
                    {isFetching ? '조회 중...' : `${rows.length.toLocaleString()}건`}
                    {data?.meta?.exists === false && ' — 해당 버전의 집계 테이블이 아직 없습니다'}
                    {!perCharacter && (
                        <span className="ml-2 text-xs">
                            주 사용 캐릭터 사용률 = 그 캐릭터·무기의 전체 게임 중 이 항목을 쓴 비율
                        </span>
                    )}
                </span>
                {data?.meta?.table && <span className="font-mono text-xs">{data.meta.table}</span>}
            </div>

            <div className="overflow-x-auto rounded-md border border-[hsl(var(--border))]">
                <table className={`w-full text-sm ${perCharacter ? 'min-w-[1100px]' : 'min-w-[1400px]'}`}>
                    <thead>
                        <tr className="border-b border-[hsl(var(--border))] bg-[hsl(var(--muted))]">
                            <th rowSpan={2} className="px-3 py-2 text-left">이름</th>
                            {perCharacter ? (
                                <>
                                    <th rowSpan={2} className="px-3 py-2 text-left">캐릭터</th>
                                    <th rowSpan={2} className="px-3 py-2 text-left">무기</th>
                                </>
                            ) : (
                                <th rowSpan={2} className="px-3 py-2 text-left">주 사용 캐릭터 (사용률순)</th>
                            )}
                            <th colSpan={5} className="border-l border-[hsl(var(--border))] px-3 py-2 text-center font-bold">
                                TOP4 (1~4위)
                            </th>
                            <th colSpan={5} className="border-l border-[hsl(var(--border))] px-3 py-2 text-center text-[hsl(var(--muted-foreground))]">
                                전체 (1~8위)
                            </th>
                            <th rowSpan={2} className="border-l border-[hsl(var(--border))] px-3 py-2 text-right">TOP4율</th>
                        </tr>
                        <tr className="border-b border-[hsl(var(--border))] bg-[hsl(var(--muted))] text-xs">
                            <th className="border-l border-[hsl(var(--border))] px-3 py-1 text-right">게임수</th>
                            <th className="px-3 py-1 text-right">평균획득</th>
                            <th className="px-3 py-1 text-right">평균TK</th>
                            <th className="px-3 py-1 text-right">이득률/점수</th>
                            <th className="px-3 py-1 text-right">손실률/점수</th>
                            <th className="border-l border-[hsl(var(--border))] px-3 py-1 text-right">게임수</th>
                            <th className="px-3 py-1 text-right">평균획득</th>
                            <th className="px-3 py-1 text-right">평균TK</th>
                            <th className="px-3 py-1 text-right">이득률/점수</th>
                            <th className="px-3 py-1 text-right">손실률/점수</th>
                        </tr>
                    </thead>
                    <tbody>
                        {groupKeys.map((key) => (
                            <Fragment key={key || 'ungrouped'}>
                                {key && (
                                    <tr className="border-b border-[hsl(var(--border))] bg-[hsl(var(--accent))]">
                                        <td colSpan={columnCount} className="px-3 py-1.5 text-xs font-bold">
                                            {grouped[key][0].group_label || key}
                                            <span className="ml-2 font-normal text-[hsl(var(--muted-foreground))]">
                                                {grouped[key].length}건
                                            </span>
                                        </td>
                                    </tr>
                                )}
                                {grouped[key].map((r) => (
                            <tr
                                key={`${r.item_id}-${r.character_id ?? 'all'}-${r.weapon_type ?? 'all'}`}
                                className="border-b border-[hsl(var(--border))] align-top hover:bg-[hsl(var(--muted))]"
                            >
                                <td className="px-3 py-2">
                                    <div className="flex items-center gap-2">
                                        <img
                                            src={r.image}
                                            alt=""
                                            className="h-7 w-7 shrink-0 rounded"
                                            loading="lazy"
                                            onError={(e) => { (e.currentTarget as HTMLImageElement).style.visibility = 'hidden'; }}
                                        />
                                        <span>
                                            {r.item_name}
                                            {r.sub_group_label && (
                                                <span className="ml-2 rounded bg-[hsl(var(--muted))] px-1.5 py-0.5 text-xs text-[hsl(var(--muted-foreground))]">
                                                    {r.sub_group_label}
                                                </span>
                                            )}
                                        </span>
                                    </div>
                                </td>
                                {perCharacter ? (
                                    <>
                                        <td className="px-3 py-2">{r.character_name}</td>
                                        <td className="px-3 py-2 text-[hsl(var(--muted-foreground))]">
                                            {weaponLabel(r.weapon_type)}
                                        </td>
                                    </>
                                ) : (
                                    <td className="min-w-[280px] px-3 py-2">
                                        <TopCharacterList
                                            characters={r.top_characters ?? []}
                                            expanded={!!expanded[`${type}-${r.item_id}`]}
                                            onToggle={() => toggle(`${type}-${r.item_id}`)}
                                        />
                                    </td>
                                )}

                                <td className="border-l border-[hsl(var(--border))] px-3 py-2 text-right font-medium">
                                    {r.top.game_count.toLocaleString()}
                                </td>
                                <td className={`px-3 py-2 text-right font-medium ${signClass(r.top.avg_mmr_gain)}`}>
                                    {num(r.top.avg_mmr_gain)}
                                </td>
                                <td className="px-3 py-2 text-right">{num(r.top.avg_team_kill, 2)}</td>
                                <td className="px-3 py-2 text-right">
                                    {num(r.top.positive_percent, 1)}%
                                    <span className="ml-1 text-xs text-[hsl(var(--muted-foreground))]">{num(r.top.positive_avg)}</span>
                                </td>
                                <td className="px-3 py-2 text-right">
                                    {num(r.top.negative_percent, 1)}%
                                    <span className="ml-1 text-xs text-[hsl(var(--muted-foreground))]">{num(r.top.negative_avg)}</span>
                                </td>
                                <td className="border-l border-[hsl(var(--border))] px-3 py-2 text-right text-[hsl(var(--muted-foreground))]">
                                    {r.all.game_count.toLocaleString()}
                                </td>
                                <td className={`px-3 py-2 text-right ${signClass(r.all.avg_mmr_gain)}`}>
                                    {num(r.all.avg_mmr_gain)}
                                </td>
                                <td className="px-3 py-2 text-right text-[hsl(var(--muted-foreground))]">
                                    {num(r.all.avg_team_kill, 2)}
                                </td>
                                <td className="px-3 py-2 text-right text-[hsl(var(--muted-foreground))]">
                                    {num(r.all.positive_percent, 1)}%
                                    <span className="ml-1 text-xs">{num(r.all.positive_avg)}</span>
                                </td>
                                <td className="px-3 py-2 text-right text-[hsl(var(--muted-foreground))]">
                                    {num(r.all.negative_percent, 1)}%
                                    <span className="ml-1 text-xs">{num(r.all.negative_avg)}</span>
                                </td>
                                <td className="border-l border-[hsl(var(--border))] px-3 py-2 text-right">
                                    {num(r.top_rate, 1)}%
                                </td>
                            </tr>
                                ))}
                            </Fragment>
                        ))}
                        {rows.length === 0 && !isFetching && (
                            <tr>
                                <td colSpan={columnCount} className="px-3 py-10 text-center text-[hsl(var(--muted-foreground))]">
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

/** 주 사용 캐릭터 목록 (기본 3명, 더보기로 서버가 준 만큼 전부) */
function TopCharacterList({
    characters,
    expanded,
    onToggle,
}: {
    characters: TopCharacter[];
    expanded: boolean;
    onToggle: () => void;
}) {
    if (characters.length === 0) {
        return <span className="text-xs text-[hsl(var(--muted-foreground))]">-</span>;
    }

    const visible = expanded ? characters : characters.slice(0, COLLAPSED_CHARACTERS);
    const hidden = characters.length - COLLAPSED_CHARACTERS;

    return (
        <div className="space-y-1">
            {visible.map((c, i) => (
                <div
                    key={`${c.character_id}-${c.weapon_type}`}
                    className="flex items-center gap-2 text-xs"
                    title={`${c.game_count.toLocaleString()} / ${c.total_count.toLocaleString()}게임 · 평균획득 ${num(c.avg_mmr_gain)} · TOP4율 ${num(c.top_rate)}%`}
                >
                    <span className="w-4 text-right text-[hsl(var(--muted-foreground))]">{i + 1}</span>
                    <span className="whitespace-nowrap font-medium">
                        {c.character_name}
                        <span className="ml-1 font-normal text-[hsl(var(--muted-foreground))]">{c.weapon_label}</span>
                    </span>
                    <span className="ml-auto whitespace-nowrap font-medium">{num(c.usage_rate)}%</span>
                    <span className="w-14 whitespace-nowrap text-right text-[hsl(var(--muted-foreground))]">
                        {c.game_count.toLocaleString()}판
                    </span>
                    <span className={`w-12 whitespace-nowrap text-right ${signClass(c.avg_mmr_gain)}`}>
                        {num(c.avg_mmr_gain)}
                    </span>
                </div>
            ))}
            {hidden > 0 && (
                <button
                    type="button"
                    onClick={onToggle}
                    className="text-xs text-[hsl(var(--primary))] hover:underline"
                >
                    {expanded ? '접기' : `+${hidden}명 더보기`}
                </button>
            )}
        </div>
    );
}
