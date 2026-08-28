import { useEffect, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import api from '@/lib/axios';
import { PageHeader } from '@/components/shared/PageHeader';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { TrendChart, TrendPoint } from './TrendChart';

interface Options {
    characters: { value: string; label: string }[];
    weapons: { value: string; label: string }[];
    tiers: string[];
    range: { first_date: string | null; last_date: string | null; days: number };
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

const RANGES = [
    { value: '', label: '전체 기간' },
    { value: '90', label: '최근 90일' },
    { value: '30', label: '최근 30일' },
    { value: '15', label: '최근 15일 (상세와 동일)' },
];

export default function SummaryTrendPage() {
    const [characterName, setCharacterName] = useState('');
    const [weaponType, setWeaponType] = useState('');
    const [minTier, setMinTier] = useState('Diamond');
    const [days, setDays] = useState('');

    const { data: options } = useQuery<Options>({
        queryKey: ['summary-trend', 'options', characterName],
        queryFn: async () =>
            (await api.get('/summary-trend/options', { params: { character_name: characterName || undefined } })).data.data,
    });

    // 첫 로드 시 캐릭터/무기를 채워 빈 화면을 보여주지 않는다
    useEffect(() => {
        if (!characterName && options?.characters?.length) {
            setCharacterName(options.characters[0].value);
        }
    }, [options, characterName]);

    useEffect(() => {
        if (options?.weapons?.length && !options.weapons.some((w) => w.value === weaponType)) {
            setWeaponType(options.weapons[0].value);
        }
    }, [options, weaponType]);

    const enabled = Boolean(characterName && weaponType);

    const { data, isFetching } = useQuery<{ points: TrendPoint[]; weapon_type_ko: string }>({
        queryKey: ['summary-trend', characterName, weaponType, minTier, days],
        queryFn: async () =>
            (
                await api.get('/summary-trend', {
                    params: {
                        character_name: characterName,
                        weapon_type: weaponType,
                        min_tier: minTier,
                        days: days || undefined,
                    },
                })
            ).data,
        enabled,
    });

    const points = data?.points ?? [];

    return (
        <div className="space-y-6">
            <PageHeader
                title="지표 추이"
                description="일자별 스냅샷으로 캐릭터 지표의 변동을 봅니다. 캐릭터 상세는 최근 15일만 보여주고, 그보다 긴 기간은 여기서 확인합니다."
            />

            <Card>
                <CardContent className="grid gap-4 pt-6 md:grid-cols-2 lg:grid-cols-4">
                    <div className="space-y-2">
                        <Label>캐릭터</Label>
                        <Select value={characterName} onValueChange={(v) => { setCharacterName(v); setWeaponType(''); }}>
                            <SelectTrigger><SelectValue placeholder="선택..." /></SelectTrigger>
                            <SelectContent>
                                {(options?.characters ?? []).map((c) => (
                                    <SelectItem key={c.value} value={c.value}>{c.label}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="space-y-2">
                        <Label>무기군</Label>
                        <Select value={weaponType} onValueChange={setWeaponType}>
                            <SelectTrigger><SelectValue placeholder="선택..." /></SelectTrigger>
                            <SelectContent>
                                {(options?.weapons ?? []).map((w) => (
                                    <SelectItem key={w.value} value={w.value}>{w.label}</SelectItem>
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
                        <Label>기간</Label>
                        <Select value={days || '__all__'} onValueChange={(v) => setDays(v === '__all__' ? '' : v)}>
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                {RANGES.map((r) => (
                                    <SelectItem key={r.value || '__all__'} value={r.value || '__all__'}>{r.label}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </CardContent>
            </Card>

            <div className="flex items-center justify-between text-sm text-[hsl(var(--muted-foreground))]">
                <span>{isFetching ? '조회 중...' : `${points.length}개 시점`}</span>
                {options?.range?.first_date && (
                    <span>
                        적재 범위 {options.range.first_date} ~ {options.range.last_date} ({options.range.days}일)
                    </span>
                )}
            </div>

            {points.length > 0 ? (
                <>
                    <div className="grid gap-4 lg:grid-cols-2">
                        <TrendChart title="티어" points={points} metric="meta_score" kind="tier" digits={1} />
                        <TrendChart title="픽률" points={points} metric="pick_rate" unit="%" digits={2} />
                        <TrendChart title="평균획득점수" points={points} metric="avg_mmr_gain" digits={1} />
                        <TrendChart title="승률" points={points} metric="win_rate" unit="%" digits={2} />
                        <TrendChart title="TOP4율" points={points} metric="top4_rate" unit="%" digits={2} />
                        <TrendChart title="평균TK" points={points} metric="avg_team_kill" digits={2} />
                    </div>

                    <div className="overflow-x-auto rounded-md border border-[hsl(var(--border))]">
                        <table className="w-full min-w-[760px] text-sm">
                            <thead>
                                <tr className="border-b border-[hsl(var(--border))] bg-[hsl(var(--muted))]">
                                    <th className="px-3 py-2 text-left">일자</th>
                                    <th className="px-3 py-2 text-left">버전</th>
                                    <th className="px-3 py-2 text-left">티어</th>
                                    <th className="px-3 py-2 text-right">메타점수</th>
                                    <th className="px-3 py-2 text-right">픽률</th>
                                    <th className="px-3 py-2 text-right">평균획득</th>
                                    <th className="px-3 py-2 text-right">승률</th>
                                    <th className="px-3 py-2 text-right">TOP4율</th>
                                    <th className="px-3 py-2 text-right">평균TK</th>
                                    <th className="px-3 py-2 text-right">게임수</th>
                                </tr>
                            </thead>
                            <tbody>
                                {[...points].reverse().map((p) => (
                                    <tr key={p.date + p.version} className="border-b border-[hsl(var(--border))]">
                                        <td className="px-3 py-2">{p.date}</td>
                                        <td className="px-3 py-2">{p.version}</td>
                                        <td className="px-3 py-2">{p.meta_tier ?? '-'}</td>
                                        <td className="px-3 py-2 text-right">{p.meta_score?.toFixed(1) ?? '-'}</td>
                                        <td className="px-3 py-2 text-right">{p.pick_rate?.toFixed(2) ?? '-'}%</td>
                                        <td className="px-3 py-2 text-right">{p.avg_mmr_gain?.toFixed(1) ?? '-'}</td>
                                        <td className="px-3 py-2 text-right">{p.win_rate?.toFixed(2) ?? '-'}%</td>
                                        <td className="px-3 py-2 text-right">{p.top4_rate?.toFixed(2) ?? '-'}%</td>
                                        <td className="px-3 py-2 text-right">{p.avg_team_kill?.toFixed(2) ?? '-'}</td>
                                        <td className="px-3 py-2 text-right">{p.game_count.toLocaleString()}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </>
            ) : (
                !isFetching && (
                    <p className="py-16 text-center text-[hsl(var(--muted-foreground))]">
                        해당 조건의 스냅샷이 없습니다.
                    </p>
                )
            )}
        </div>
    );
}
