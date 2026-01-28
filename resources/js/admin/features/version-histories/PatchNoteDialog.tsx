import { useEffect, useState } from 'react';
import { useForm, Controller } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Check, ChevronsUpDown } from 'lucide-react';
import { PatchNote, Character, Equipment, SelectOption } from '@/types';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import { cn } from '@/lib/utils';

const patchNoteSchema = z.object({
    category: z.string().min(1, '구분을 선택해주세요'),
    patch_type: z.string().min(1, '패치 유형을 선택해주세요'),
    target_id: z.coerce.number().nullable(),
    weapon_type: z.string().nullable(),
    skill_type: z.string().nullable(),
    content: z.string().min(1, '내용을 입력해주세요'),
});

type PatchNoteFormData = z.infer<typeof patchNoteSchema>;

const categories = ['캐릭터', '특성', '아이템', '시스템', '전술스킬', '기타'];
const patchTypes = ['버프', '너프', '조정', '리워크', '신규', '삭제'];
const weaponTypes = [
    '단검', '톤파', '활', '석궁', '글러브', '쌍검', '투척', '양손검', '도끼',
    '창', '방망이', '망치', '채찍', '암기', '레이피어', '기타', '쌍절곤',
    '권총', '돌격소총', '저격총', '카메라', 'VF의수', '아르카나',
];
const skillTypes = ['Q', 'W', 'E', 'R', 'T', '기본', '패시브'];

interface TargetComboboxProps {
    options: { value: string | number; label: string }[];
    value: number | null;
    onChange: (value: number | null) => void;
    selectedLabel?: string;
}

function TargetCombobox({ options, value, onChange, selectedLabel }: TargetComboboxProps) {
    const [open, setOpen] = useState(false);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    className="w-full justify-between font-normal"
                >
                    {selectedLabel || '선택...'}
                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-[--radix-popover-trigger-width] p-0" align="start">
                <Command>
                    <CommandInput placeholder="검색..." />
                    <CommandList>
                        <CommandEmpty>검색 결과가 없습니다.</CommandEmpty>
                        <CommandGroup>
                            {options.map((opt) => (
                                <CommandItem
                                    key={opt.value}
                                    value={opt.label}
                                    onSelect={() => {
                                        onChange(opt.value === value ? null : Number(opt.value));
                                        setOpen(false);
                                    }}
                                >
                                    <Check
                                        className={cn(
                                            "mr-2 h-4 w-4",
                                            value === opt.value ? "opacity-100" : "opacity-0"
                                        )}
                                    />
                                    {opt.label}
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}

interface PatchNoteDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    patchNote: PatchNote | null;
    onSubmit: (data: Partial<PatchNote>) => void;
    isSubmitting: boolean;
    characters: Character[];
    equipment: Equipment[];
    traits: SelectOption[];
    tacticalSkills: SelectOption[];
}

export function PatchNoteDialog({
    open,
    onOpenChange,
    patchNote,
    onSubmit,
    isSubmitting,
    characters,
    equipment,
    traits,
    tacticalSkills,
}: PatchNoteDialogProps) {
    const {
        control,
        handleSubmit,
        watch,
        reset,
        formState: { errors },
    } = useForm<PatchNoteFormData>({
        resolver: zodResolver(patchNoteSchema),
        defaultValues: {
            category: '',
            patch_type: '',
            target_id: null,
            weapon_type: null,
            skill_type: null,
            content: '',
        },
    });

    const category = watch('category');

    useEffect(() => {
        if (patchNote) {
            reset({
                category: patchNote.category,
                patch_type: patchNote.patch_type,
                target_id: patchNote.target_id,
                weapon_type: patchNote.weapon_type,
                skill_type: patchNote.skill_type,
                content: patchNote.content,
            });
        } else {
            reset({
                category: '',
                patch_type: '',
                target_id: null,
                weapon_type: null,
                skill_type: null,
                content: '',
            });
        }
    }, [patchNote, reset, open]);

    const getTargetOptions = () => {
        switch (category) {
            case '캐릭터':
                return characters.map((c) => ({ value: c.id, label: c.name }));
            case '아이템':
                return equipment.map((e) => ({ value: e.id, label: e.name || '' }));
            case '특성':
                return traits;
            case '전술스킬':
                return tacticalSkills;
            default:
                return [];
        }
    };

    const showTargetSelect = ['캐릭터', '아이템', '특성', '전술스킬'].includes(category);
    const showWeaponType = category === '캐릭터';
    const showSkillType = category === '캐릭터';

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>{patchNote ? '패치노트 수정' : '패치노트 추가'}</DialogTitle>
                </DialogHeader>

                <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label>구분 *</Label>
                            <Controller
                                name="category"
                                control={control}
                                render={({ field }) => (
                                    <Select value={field.value} onValueChange={field.onChange}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="선택..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {categories.map((cat) => (
                                                <SelectItem key={cat} value={cat}>
                                                    {cat}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                )}
                            />
                            {errors.category && (
                                <p className="text-sm text-[hsl(var(--destructive))]">
                                    {errors.category.message}
                                </p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label>패치 유형 *</Label>
                            <Controller
                                name="patch_type"
                                control={control}
                                render={({ field }) => (
                                    <Select value={field.value} onValueChange={field.onChange}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="선택..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {patchTypes.map((type) => (
                                                <SelectItem key={type} value={type}>
                                                    {type}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                )}
                            />
                            {errors.patch_type && (
                                <p className="text-sm text-[hsl(var(--destructive))]">
                                    {errors.patch_type.message}
                                </p>
                            )}
                        </div>
                    </div>

                    {showTargetSelect && (
                        <div className="space-y-2">
                            <Label>대상</Label>
                            <Controller
                                name="target_id"
                                control={control}
                                render={({ field }) => {
                                    const options = getTargetOptions();
                                    const selectedOption = options.find(
                                        (opt) => opt.value === field.value
                                    );
                                    return (
                                        <TargetCombobox
                                            options={options}
                                            value={field.value}
                                            onChange={field.onChange}
                                            selectedLabel={selectedOption?.label}
                                        />
                                    );
                                }}
                            />
                        </div>
                    )}

                    {showWeaponType && (
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label>무기</Label>
                                <Controller
                                    name="weapon_type"
                                    control={control}
                                    render={({ field }) => (
                                        <Select
                                            value={field.value || ''}
                                            onValueChange={(v) => field.onChange(v || null)}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="선택 안함" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {weaponTypes.map((weapon) => (
                                                    <SelectItem key={weapon} value={weapon}>
                                                        {weapon}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    )}
                                />
                            </div>

                            {showSkillType && (
                                <div className="space-y-2">
                                    <Label>스킬</Label>
                                    <Controller
                                        name="skill_type"
                                        control={control}
                                        render={({ field }) => (
                                            <Select
                                                value={field.value || ''}
                                                onValueChange={(v) => field.onChange(v || null)}
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="선택 안함" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {skillTypes.map((skill) => (
                                                        <SelectItem key={skill} value={skill}>
                                                            {skill}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        )}
                                    />
                                </div>
                            )}
                        </div>
                    )}

                    <div className="space-y-2">
                        <Label>패치 내용 *</Label>
                        <Controller
                            name="content"
                            control={control}
                            render={({ field }) => (
                                <Textarea rows={5} {...field} />
                            )}
                        />
                        {errors.content && (
                            <p className="text-sm text-[hsl(var(--destructive))]">
                                {errors.content.message}
                            </p>
                        )}
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            취소
                        </Button>
                        <Button type="submit" disabled={isSubmitting}>
                            {isSubmitting ? '저장 중...' : '저장'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
