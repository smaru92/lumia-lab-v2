import { useState } from 'react';

export interface TrendPoint {
    date: string;
    version: string;
    meta_tier: string | null;
    meta_score: number | null;
    game_count: number;
    pick_rate: number | null;
    win_rate: number | null;
    top4_rate: number | null;
    avg_mmr_gain: number | null;
    avg_team_kill: number | null;
}

interface Props {
    title: string;
    points: TrendPoint[];
    metric: keyof TrendPoint;
    /** 티어는 순위형이라 축을 뒤집고 눈금 라벨이 다르다 */
    kind?: 'number' | 'tier';
    unit?: string;
    digits?: number;
}

/** 밝은 배경에서 대비/채도 검증을 통과한 선 색 */
const LINE = '#2a78d6';
const GRID = '#e5e5e2';
const AXIS = '#8a8a85';
const INK = '#52514e';

const TIER_STEPS = ['OP', '1', '2', '3', '4', '5', 'RIP'];

const W = 640;
const H = 190;
const PAD = { l: 46, r: 16, t: 16, b: 30 };

export function TrendChart({ title, points, metric, kind = 'number', unit = '', digits = 1 }: Props) {
    const [hover, setHover] = useState<number | null>(null);

    const innerW = W - PAD.l - PAD.r;
    const innerH = H - PAD.t - PAD.b;

    const values = points.map((p) =>
        kind === 'tier' ? tierIndex(p.meta_tier) : (p[metric] as number | null)
    );
    const valid = values.filter((v): v is number => v !== null && v !== undefined);

    if (valid.length < 2) {
        return (
            <figure className="m-0 rounded-md border border-[hsl(var(--border))] p-3">
                <figcaption className="mb-1 text-sm font-bold">{title}</figcaption>
                <p className="py-10 text-center text-sm text-[hsl(var(--muted-foreground))]">
                    데이터가 2개 시점 미만입니다.
                </p>
            </figure>
        );
    }

    let min: number;
    let max: number;
    if (kind === 'tier') {
        min = 0;
        max = TIER_STEPS.length - 1;
    } else {
        min = Math.min(...valid);
        max = Math.max(...valid);
        const span = max - min;
        const pad = span === 0 ? Math.max(Math.abs(max) * 0.1, 1) : span * 0.15;
        min -= pad;
        max += pad;
    }

    const x = (i: number) => PAD.l + (points.length === 1 ? innerW / 2 : (i / (points.length - 1)) * innerW);
    const y = (v: number) => {
        const t = (v - min) / (max - min || 1);
        // 티어는 OP(0)가 위로 오도록 뒤집지 않는다
        return PAD.t + (kind === 'tier' ? t * innerH : (1 - t) * innerH);
    };

    const ticks =
        kind === 'tier'
            ? [0, 3, 6].map((i) => ({ v: i, label: TIER_STEPS[i] }))
            : [0, 0.5, 1].map((t) => {
                  const v = min + (max - min) * t;
                  return { v, label: fmt(v, digits, unit) };
              });

    const path = points
        .map((_, i) => {
            const v = values[i];
            if (v === null || v === undefined) return null;
            return `${x(i).toFixed(1)} ${y(v).toFixed(1)}`;
        })
        .filter(Boolean)
        .map((seg, i) => (i === 0 ? `M${seg}` : `L${seg}`))
        .join(' ');

    const lastIdx = values.length - 1;
    const lastVal = values[lastIdx];
    const hovered = hover !== null ? points[hover] : null;

    return (
        <figure className="m-0 rounded-md border border-[hsl(var(--border))] p-3">
            <figcaption className="mb-1 text-sm font-bold">{title}</figcaption>

            <div className="relative">
                <svg
                    viewBox={`0 0 ${W} ${H}`}
                    className="block h-[180px] w-full"
                    preserveAspectRatio="none"
                    role="img"
                    aria-label={`${title} 추이`}
                    onMouseMove={(e) => {
                        const rect = e.currentTarget.getBoundingClientRect();
                        const ratio = (e.clientX - rect.left) / rect.width;
                        setHover(Math.max(0, Math.min(points.length - 1, Math.round(ratio * (points.length - 1)))));
                    }}
                    onMouseLeave={() => setHover(null)}
                >
                    {ticks.map((t) => (
                        <g key={t.label}>
                            <line x1={PAD.l} y1={y(t.v)} x2={W - PAD.r} y2={y(t.v)} stroke={GRID} strokeWidth={1} />
                            <text x={PAD.l - 6} y={y(t.v)} textAnchor="end" dominantBaseline="middle" fontSize={9} fill={AXIS}>
                                {t.label}
                            </text>
                        </g>
                    ))}

                    <path d={path} fill="none" stroke={LINE} strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" />

                    {points.map((_, i) => {
                        const v = values[i];
                        if (v === null || v === undefined) return null;
                        const last = i === lastIdx;
                        return (
                            <circle
                                key={i}
                                cx={x(i)}
                                cy={y(v)}
                                r={last ? 4 : 2.5}
                                fill={LINE}
                                stroke="#fff"
                                strokeWidth={last ? 2 : 1}
                            />
                        );
                    })}

                    {hover !== null && (
                        <line x1={x(hover)} y1={PAD.t} x2={x(hover)} y2={H - PAD.b} stroke={AXIS} strokeWidth={1} strokeDasharray="3 3" />
                    )}

                    {lastVal !== null && lastVal !== undefined && (
                        <text
                            x={Math.min(x(lastIdx), W - PAD.r - 4)}
                            y={Math.max(y(lastVal) - 9, PAD.t + 4)}
                            textAnchor="end"
                            fontSize={10}
                            fontWeight={700}
                            fill={INK}
                        >
                            {kind === 'tier' ? points[lastIdx].meta_tier : fmt(lastVal, digits, unit)}
                        </text>
                    )}

                    <text x={PAD.l} y={H - 8} fontSize={9} fill={AXIS}>{points[0].date}</text>
                    <text x={W - PAD.r} y={H - 8} textAnchor="end" fontSize={9} fill={AXIS}>
                        {points[points.length - 1].date}
                    </text>
                </svg>

                {hovered && (
                    <div
                        className="pointer-events-none absolute top-1 rounded border border-[hsl(var(--border))] bg-[hsl(var(--background))] px-2 py-1 text-xs shadow"
                        style={{ left: `min(${(hover! / Math.max(points.length - 1, 1)) * 100}%, calc(100% - 130px))` }}
                    >
                        <strong>
                            {kind === 'tier'
                                ? `${hovered.meta_tier ?? '-'} 티어`
                                : fmt((hovered[metric] as number | null) ?? 0, digits, unit)}
                        </strong>
                        <br />
                        {hovered.date} · {hovered.version}
                        <br />
                        <span className="text-[hsl(var(--muted-foreground))]">
                            게임 {hovered.game_count.toLocaleString()}
                        </span>
                    </div>
                )}
            </div>
        </figure>
    );
}

function tierIndex(tier: string | null): number | null {
    if (!tier) return null;
    const i = TIER_STEPS.indexOf(String(tier));
    return i === -1 ? null : i;
}

function fmt(v: number, digits: number, unit: string) {
    return v.toFixed(digits) + unit;
}
