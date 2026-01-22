import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { X, Plus } from 'lucide-react';
import api from '@/lib/axios';
import { EquipmentSkill } from '@/types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { toast } from '@/hooks/useToast';

interface EquipmentSkillsManagerProps {
    equipmentId: number;
    currentSkills: EquipmentSkill[];
    allSkills: EquipmentSkill[];
}

export function EquipmentSkillsManager({
    equipmentId,
    currentSkills,
    allSkills,
}: EquipmentSkillsManagerProps) {
    const [selectedSkillId, setSelectedSkillId] = useState<string>('');
    const queryClient = useQueryClient();

    const availableSkills = allSkills.filter(
        (skill) => !currentSkills.some((cs) => cs.id === skill.id)
    );

    const syncMutation = useMutation({
        mutationFn: async (skillIds: number[]) => {
            const response = await api.post(`/equipment/${equipmentId}/skills`, {
                skill_ids: skillIds,
            });
            return response.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['equipment', String(equipmentId)] });
            toast({
                title: '스킬 연결 변경',
                description: '장비 스킬이 업데이트되었습니다.',
            });
        },
        onError: () => {
            toast({
                title: '실패',
                description: '스킬 업데이트에 실패했습니다.',
                variant: 'destructive',
            });
        },
    });

    const handleAddSkill = () => {
        if (!selectedSkillId) return;
        const newSkillIds = [...currentSkills.map((s) => s.id), Number(selectedSkillId)];
        syncMutation.mutate(newSkillIds);
        setSelectedSkillId('');
    };

    const handleRemoveSkill = (skillId: number) => {
        const newSkillIds = currentSkills.filter((s) => s.id !== skillId).map((s) => s.id);
        syncMutation.mutate(newSkillIds);
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center gap-2">
                <Select value={selectedSkillId} onValueChange={setSelectedSkillId}>
                    <SelectTrigger className="w-[300px]">
                        <SelectValue placeholder="스킬 선택..." />
                    </SelectTrigger>
                    <SelectContent>
                        {availableSkills.map((skill) => (
                            <SelectItem key={skill.id} value={String(skill.id)}>
                                {skill.name} ({skill.grade})
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <Button
                    type="button"
                    onClick={handleAddSkill}
                    disabled={!selectedSkillId || syncMutation.isPending}
                >
                    <Plus className="mr-2 h-4 w-4" />
                    추가
                </Button>
            </div>

            <div className="flex flex-wrap gap-2">
                {currentSkills.length === 0 ? (
                    <p className="text-sm text-[hsl(var(--muted-foreground))]">
                        연결된 스킬이 없습니다.
                    </p>
                ) : (
                    currentSkills.map((skill) => (
                        <Badge
                            key={skill.id}
                            variant="secondary"
                            className="flex items-center gap-1 px-3 py-1"
                        >
                            {skill.name}
                            <button
                                type="button"
                                onClick={() => handleRemoveSkill(skill.id)}
                                className="ml-1 hover:text-[hsl(var(--destructive))]"
                                disabled={syncMutation.isPending}
                            >
                                <X className="h-3 w-3" />
                            </button>
                        </Badge>
                    ))
                )}
            </div>
        </div>
    );
}
