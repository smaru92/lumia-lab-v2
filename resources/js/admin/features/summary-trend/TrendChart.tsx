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
    /** 티어 차트는 메타스코어를 그리되 값 표기에 등급을 함께 보여준다 */
    kind?: 'number' | 'tier';
    unit?: string;
    digits?: number;
}

/** 밝은 배경에서 대비/채도 검증을 통과한 선 색 */
const LINE = '#2a78d6';
const GRID = '#e5e5e2';
const AXIS = '#8a8a85';
const INK = '#52514e';

/**
 * 티어 경계 점수는 고정값이다 (GameResultService::getMetaDataNew)
 * 각 경계선은 "이 선 위부터 해당 티어"를 뜻한다.
 */
const TIER_BOUNDS = [
    { score: 5, tier: 'OP', color: '#8A2BE2' },
    { score: 3, tier: '1', color: '#E23B3B' },
    { score: 1, tier: '2', color: '#FF8C00' },
    { score: -1, tier: '3', color: '#F4C020' },
    { score: -3, tier: '4', color: '#5CBF6A' },
    { score: -5, tier: '5', color: '#4A9DF0' },
];

const W = 640;
const H = 190;
const PAD = { l: 46, r: 16, t: 16, b: 42 };

export function TrendChart({ title, points, metric, kind = 'number', unit = '', digits = 1 }: Props) {
    const [hover, setHover] = useState<number | null>(null);

    const innerW = W - PAD.l - PAD.r;
    const innerH = H - PAD.t - PAD.b;

    // 티어 차트도 등급 계단이 아니라 메타스코어 연속값을 그린다
    const values = points.map((p) => p[metric] as number | null);
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

    let min = Math.min(...valid);
    let max = Math.max(...valid);
    const span = max - min;
    const pad = span === 0 ? Math.max(Math.abs(max) * 0.1, 1) : span * 0.15;
    min -= pad;
    max += pad;

    // 티어 차트는 데이터가 걸쳐 있는 티어 구간이 온전히 보이도록 경계까지 넓힌다
    if (kind === 'tier' && !TIER_BOUNDS.some((b) => b.score >= min && b.score <= max)) {
        const above = TIER_BOUNDS.filter((b) => b.score > max).pop();
        const below = TIER_BOUNDS.filter((b) => b.score < min)[0];
        if (above) max = above.score;
        if (below) min = below.score;
    }

    const x = (i: number) => PAD.l + (points.length === 1 ? innerW / 2 : (i / (points.length - 1)) * innerW);
    const y = (v: number) => {
        const t = (v - min) / (max - min || 1);
        return PAD.t + (1 - t) * innerH;
    };

    const ticks: { v: number; label: string; color?: string }[] =
        kind === 'tier'
            ? TIER_BOUNDS.filter((b) => b.score >= min && b.score <= max).map((b) => ({
                  v: b.score,
                  label: b.tier,
                  color: b.color,
              }))
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

    // 버전이 바뀌는 지점만 추린다 (라벨이 겹치면 표기는 생략하고 선만 남긴다)
    let lastLabelX = -999;
    const versionMarks = points
        .map((p, index) => ({ index, version: p.version }))
        .filter((m) => m.index === 0 || points[m.index].version !== points[m.index - 1].version)
        .map((m) => {
            const px = x(m.index);
            const showLabel = px - lastLabelX >= 56;
            if (showLabel) lastLabelX = px;
            return { ...m, showLabel };
        });

    return (
        <figure className="m-0 rounded-md border border-[hsl(var(--border))] p-3">
            <figcaption className="mb-1 text-sm font-bold">{title}</figcaption>

            <div className="relative">
                <svg
                    viewBox={`0 0 ${W} ${H}`}
                    className="block h-auto w-full"
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
                            <line
                                x1={PAD.l}
                                y1={y(t.v)}
                                x2={W - PAD.r}
                                y2={y(t.v)}
                                stroke={t.color ?? GRID}
                                strokeOpacity={t.color ? 0.55 : 1}
                                strokeWidth={1}
                            />
                            <text
                                x={PAD.l - 6}
                                y={y(t.v)}
                                textAnchor="end"
                                dominantBaseline="middle"
                                fontSize={10}
                                fontWeight={t.color ? 700 : 400}
                                fill={t.color ?? AXIS}
                            >
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
                            stroke="#fff"
                            strokeWidth={3}
                            paintOrder="stroke"
                            strokeLinejoin="round"
                        >
                            {valueLabel(points[lastIdx], lastVal, kind, digits, unit)}
                        </text>
                    )}

                    {/* 버전이 바뀌는 지점에 경계선과 버전명 - 지표 변화가 패치 때문인지 읽히도록 */}
                    {versionMarks.map((m) => (
                        <g key={m.index}>
                            {m.index > 0 && (
                                <line
                                    x1={x(m.index)}
                                    y1={PAD.t}
                                    x2={x(m.index)}
                                    y2={H - PAD.b}
                                    stroke={AXIS}
                                    strokeWidth={1}
                                    strokeDasharray="2 3"
                                    strokeOpacity={0.5}
                                />
                            )}
                            {m.showLabel && (
                                <text
                                    x={m.index === 0 ? PAD.l : x(m.index)}
                                    y={H - 18}
                                    textAnchor={m.index === 0 ? 'start' : x(m.index) > W - PAD.r - 40 ? 'end' : 'middle'}
                                    fontSize={10}
                                    fontWeight={700}
                                    fill={INK}
                                >
                                    {m.version}
                                </text>
                            )}
                        </g>
                    ))}

                    <text x={PAD.l} y={H - 5} fontSize={9} fill={AXIS}>{points[0].date}</text>
                    <text x={W - PAD.r} y={H - 5} textAnchor="end" fontSize={9} fill={AXIS}>
                        {points[points.length - 1].date}
                    </text>
                </svg>

                {hovered && (
                    <div
                        className="pointer-events-none absolute top-1 rounded border border-[hsl(var(--border))] bg-[hsl(var(--background))] px-2 py-1 text-xs shadow"
                        style={{ left: `min(${(hover! / Math.max(points.length - 1, 1)) * 100}%, calc(100% - 130px))` }}
                    >
                        <strong>
                            {valueLabel(hovered, (hovered[metric] as number | null) ?? 0, kind, digits, unit)}
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

function fmt(v: number, digits: number, unit: string) {
    return v.toFixed(digits) + unit;
}

/** 티어 차트는 "5티어(-3.6)" 처럼 등급과 점수를 함께 보여준다 */
function valueLabel(point: TrendPoint, value: number, kind: 'number' | 'tier', digits: number, unit: string) {
    const num = fmt(value, digits, unit);
    if (kind !== 'tier') return num;
    return `${point.meta_tier ? point.meta_tier + '티어' : ''}(${num})`;
}
