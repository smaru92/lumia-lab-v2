import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Trash2, ChevronsUpDown, Check } from 'lucide-react';
import api from '@/lib/axios';
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import { toast } from '@/hooks/useToast';
import { cn } from '@/lib/utils';

// 고정 태그 목록
const FIXED_TAGS = ['공격력-근거리', '공격력-원거리', '스킬증폭-근거리', '스킬증폭-원거리', '탱커'];

// 무기 타입 목록 (영문 키 → 한글 이름) — 한글명 내림차순 자동 정렬
const WEAPONS: { value: string; label: string }[] = [
    { value: 'All', label: '전체 (All)' },
    { value: 'Arcana', label: '아르카나 (Arcana)' },
    { value: 'AssaultRifle', label: '돌격소총 (AssaultRifle)' },
    { value: 'Axe', label: '도끼 (Axe)' },
    { value: 'Bat', label: '방망이 (Bat)' },
    { value: 'BlackMamba', label: '블랙맘바 (BlackMamba)' },
    { value: 'Bow', label: '활 (Bow)' },
    { value: 'Camera', label: '카메라 (Camera)' },
    { value: 'CrossBow', label: '석궁 (CrossBow)' },
    { value: 'DeathAdder', label: '데스에더 (DeathAdder)' },
    { value: 'DirectFire', label: '암기 (DirectFire)' },
    { value: 'DualSword', label: '쌍검 (DualSword)' },
    { value: 'Glove', label: '글러브 (Glove)' },
    { value: 'Guitar', label: '기타 (Guitar)' },
    { value: 'Hammer', label: '망치 (Hammer)' },
    { value: 'HighAngleFire', label: '투척무기 (HighAngleFire)' },
    { value: 'Nunchaku', label: '쌍절곤 (Nunchaku)' },
    { value: 'OneHandSword', label: '단검 (OneHandSword)' },
    { value: 'Pistol', label: '권총 (Pistol)' },
    { value: 'Rapier', label: '레이피어 (Rapier)' },
    { value: 'SideWinder', label: '사이드와인더 (SideWinder)' },
    { value: 'SniperRifle', label: '저격총 (SniperRifle)' },
    { value: 'Spear', label: '창 (Spear)' },
    { value: 'Tonfa', label: '톤파 (Tonfa)' },
    { value: 'TwoHandSword', label: '양손검 (TwoHandSword)' },
    { value: 'VFArm', label: 'VF의수 (VFArm)' },
    { value: 'Whip', label: '채찍 (Whip)' },
].sort((a, b) => a.label.localeCompare(b.label, 'ko'));

const WEAPON_LABEL_MAP = Object.fromEntries(WEAPONS.map((w) => [w.value, w.label]));

interface WeaponTagEntry {
    id: number;
    name: string;
}

interface WeaponTagsData {
    character_id: number;
    weapon_tags: Record<string, WeaponTagEntry[]>;
}

// 무기 선택 Combobox
function WeaponCombobox({
    onSelect,
    usedWeapons,
}: {
    onSelect: (value: string) => void;
    usedWeapons: string[];
}) {
    const [open, setOpen] = useState(false);

    const available = WEAPONS.filter((w) => !usedWeapons.includes(w.value));

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button variant="outline" className="w-64 justify-between">
                    무기 타입 선택...
                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-64 p-0" align="start">
                <Command>
                    <CommandInput placeholder="한글 또는 영문으로 검색..." />
                    <CommandList>
                        <CommandEmpty>검색 결과가 없습니다.</CommandEmpty>
                        <CommandGroup>
                            {available.map((weapon) => (
                                <CommandItem
                                    key={weapon.value}
                                    value={weapon.label}
                                    onSelect={() => {
                                        onSelect(weapon.value);
                                        setOpen(false);
                                    }}
                                >
                                    {weapon.label}
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}

// 무기 타입 한 행
function WeaponRow({
    characterId,
    weaponType,
    currentTags,
    onUpdated,
    onRemoveWeapon,
}: {
    characterId: number;
    weaponType: string;
    currentTags: WeaponTagEntry[];
    onUpdated: () => void;
    onRemoveWeapon: () => void;
}) {
    const currentTagNames = currentTags.map((t) => t.name);
    const displayLabel = WEAPON_LABEL_MAP[weaponType] ?? weaponType;

    const syncMutation = useMutation({
        mutationFn: async (tagNames: string[]) => {
            await api.post(`/characters/${characterId}/weapon-tags`, {
                weapon_type: weaponType,
                tag_ids: currentTags
                    .filter((t) => tagNames.includes(t.name))
                    .map((t) => t.id),
                new_tags: tagNames.filter(
                    (name) => !currentTags.some((t) => t.name === name)
                ),
            });
        },
        onSuccess: () => onUpdated(),
        onError: () => {
            toast({ title: '실패', description: '태그 저장에 실패했습니다.', variant: 'destructive' });
        },
    });

    const handleToggle = (tagName: string) => {
        const isOn = currentTagNames.includes(tagName);
        const next = isOn
            ? currentTagNames.filter((n) => n !== tagName)
            : [...currentTagNames, tagName];
        syncMutation.mutate(next);
    };

    return (
        <div className="flex items-center gap-3 rounded-lg border border-[hsl(var(--border))] px-4 py-3">
            {/* 무기 이름 */}
            <span className="w-40 shrink-0 text-sm font-medium">{displayLabel}</span>

            {/* 태그 토글 */}
            <div className="flex flex-wrap gap-2 flex-1">
                {FIXED_TAGS.map((tagName) => {
                    const isOn = currentTagNames.includes(tagName);
                    return (
                        <button
                            key={tagName}
                            type="button"
                            onClick={() => handleToggle(tagName)}
                            disabled={syncMutation.isPending}
                            className={cn(
                                'rounded-full border px-3 py-1 text-xs font-medium transition-colors',
                                isOn
                                    ? 'border-[hsl(var(--primary))] bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))]'
                                    : 'border-[hsl(var(--border))] bg-transparent text-[hsl(var(--muted-foreground))] hover:border-[hsl(var(--primary))] hover:text-[hsl(var(--primary))]'
                            )}
                        >
                            {isOn && <Check className="mr-1 inline h-3 w-3" />}
                            {tagName}
                        </button>
                    );
                })}
            </div>

            {/* 삭제 */}
            <Button
                type="button"
                variant="ghost"
                size="icon"
                onClick={onRemoveWeapon}
                className="shrink-0 text-[hsl(var(--muted-foreground))] hover:text-[hsl(var(--destructive))]"
            >
                <Trash2 className="h-4 w-4" />
            </Button>
        </div>
    );
}

export function CharacterWeaponTagsManager({ characterId }: { characterId: number }) {
    const [localWeaponTypes, setLocalWeaponTypes] = useState<string[]>([]);
    const queryClient = useQueryClient();

    const { data, isLoading } = useQuery<WeaponTagsData>({
        queryKey: ['character-weapon-tags', characterId],
        queryFn: async () => {
            const response = await api.get(`/characters/${characterId}/weapon-tags`);
            return response.data;
        },
    });

    const removeWeaponMutation = useMutation({
        mutationFn: async (weaponType: string) => {
            await api.post(`/characters/${characterId}/weapon-tags`, {
                weapon_type: weaponType,
                tag_ids: [],
            });
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['character-weapon-tags', characterId] });
        },
        onError: () => {
            toast({ title: '실패', description: '무기 타입 삭제에 실패했습니다.', variant: 'destructive' });
        },
    });

    const handleUpdated = () => {
        queryClient.invalidateQueries({ queryKey: ['character-weapon-tags', characterId] });
    };

    const handleSelect = (weaponValue: string) => {
        const alreadyInDB = data && weaponValue in data.weapon_tags;
        const alreadyLocal = localWeaponTypes.includes(weaponValue);
        if (alreadyInDB || alreadyLocal) return;
        setLocalWeaponTypes((prev) => [...prev, weaponValue]);
    };

    const handleUpdatedForWeapon = (weaponType: string) => {
        handleUpdated();
        setLocalWeaponTypes((prev) => prev.filter((w) => w !== weaponType));
    };

    const handleRemoveWeapon = (weaponType: string, isLocal: boolean) => {
        if (isLocal) {
            setLocalWeaponTypes((prev) => prev.filter((w) => w !== weaponType));
        } else {
            removeWeaponMutation.mutate(weaponType);
        }
    };

    const dbEntries = data ? Object.entries(data.weapon_tags) : [];
    const localOnlyEntries: [string, WeaponTagEntry[]][] = localWeaponTypes
        .filter((w) => !dbEntries.some(([k]) => k === w))
        .map((w) => [w, []]);
    const getLabelForWeapon = (v: string) => WEAPON_LABEL_MAP[v] ?? v;
    const allEntries = [...dbEntries, ...localOnlyEntries].sort(([a], [b]) =>
        getLabelForWeapon(a).localeCompare(getLabelForWeapon(b), 'ko')
    );
    const usedWeapons = allEntries.map(([k]) => k);

    if (isLoading) {
        return <div className="text-sm text-[hsl(var(--muted-foreground))]">불러오는 중...</div>;
    }

    return (
        <div className="space-y-3">
            {/* 무기 추가 Combobox */}
            <div className="flex items-center gap-2">
                <WeaponCombobox onSelect={handleSelect} usedWeapons={usedWeapons} />
            </div>

            {allEntries.length > 0 && (
                <p className="text-xs text-[hsl(var(--muted-foreground))]">
                    태그 버튼을 클릭하면 즉시 저장됩니다.
                </p>
            )}

            {allEntries.length === 0 ? (
                <p className="text-sm text-[hsl(var(--muted-foreground))] py-2">
                    등록된 무기 타입이 없습니다. 위에서 무기 타입을 선택하세요.
                </p>
            ) : (
                <div className="space-y-2">
                    {allEntries.map(([weaponType, tags]) => {
                        const isLocal = localOnlyEntries.some(([k]) => k === weaponType);
                        return (
                            <WeaponRow
                                key={weaponType}
                                characterId={characterId}
                                weaponType={weaponType}
                                currentTags={tags}
                                onUpdated={() => handleUpdatedForWeapon(weaponType)}
                                onRemoveWeapon={() => handleRemoveWeapon(weaponType, isLocal)}
                            />
                        );
                    })}
                </div>
            )}
        </div>
    );
}
