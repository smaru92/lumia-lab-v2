import { useState } from 'react';
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
    character_id: number;
    character_name: string;
    weapon_type: string;
    top: Metrics;
    all: Metrics;
    top_rate: number;
}

interface Options {
    types: { value: string; label: string }[];
    versions: { value: string; label: string }[];
    tiers: string[];
    characters: { value: string; label: string }[];
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
    const [search, setSearch] = useState('');
    const [minGameCount, setMinGameCount] = useState('10');

    const { data: options } = useQuery<Options>({
        queryKey: ['top-rank-stats', 'options'],
        queryFn: async () => (await api.get('/top-rank-stats/options')).data.data,
    });

    const params = {
        type,
        version: version || undefined,
        min_tier: minTier,
        character_id: characterId || undefined,
        search: search || undefined,
        min_game_count: minGameCount || undefined,
    };

    const { data, isFetching } = useQuery<{ data: StatRow[]; meta: { count: number; exists: boolean; table: string } }>({
        queryKey: ['top-rank-stats', params],
        queryFn: async () => (await api.get('/top-rank-stats', { params })).data,
    });

    const rows = data?.data ?? [];

    return (
        <div className="space-y-6">
            <PageHeader
                title="TOP4 상세 지표"
                description="캐릭터·무기별로 각 특성/아이템/전술스킬이 TOP4를 기록한 게임만 따로 집계합니다."
            />

            <Card>
                <CardContent className="grid gap-4 pt-6 md:grid-cols-3 lg:grid-cols-6">
                    <div className="space-y-2">
                        <Label>종류</Label>
                        <Select value={type} onValueChange={setType}>
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
                        <Select value={characterId || '__all__'} onValueChange={(v) => setCharacterId(v === '__all__' ? '' : v)}>
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
                </CardContent>
            </Card>

            <div className="flex items-center justify-between text-sm text-[hsl(var(--muted-foreground))]">
                <span>
                    {isFetching ? '조회 중...' : `${rows.length.toLocaleString()}건`}
                    {data?.meta?.exists === false && ' — 해당 버전의 집계 테이블이 아직 없습니다'}
                </span>
                {data?.meta?.table && <span className="font-mono text-xs">{data.meta.table}</span>}
            </div>

            <div className="overflow-x-auto rounded-md border border-[hsl(var(--border))]">
                <table className="w-full min-w-[1100px] text-sm">
                    <thead>
                        <tr className="border-b border-[hsl(var(--border))] bg-[hsl(var(--muted))]">
                            <th rowSpan={2} className="px-3 py-2 text-left">이름</th>
                            <th rowSpan={2} className="px-3 py-2 text-left">캐릭터</th>
                            <th rowSpan={2} className="px-3 py-2 text-left">무기</th>
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
                        {rows.map((r) => (
                            <tr
                                key={`${r.item_id}-${r.character_id}-${r.weapon_type}`}
                                className="border-b border-[hsl(var(--border))] hover:bg-[hsl(var(--muted))]"
                            >
                                <td className="px-3 py-2">{r.item_name}</td>
                                <td className="px-3 py-2">{r.character_name}</td>
                                <td className="px-3 py-2 text-[hsl(var(--muted-foreground))]">{r.weapon_type}</td>

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
                        {rows.length === 0 && !isFetching && (
                            <tr>
                                <td colSpan={14} className="px-3 py-10 text-center text-[hsl(var(--muted-foreground))]">
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
