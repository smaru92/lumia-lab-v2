import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/axios';
import { EquipmentSkill } from '@/types';
import { PageHeader } from '@/components/shared/PageHeader';
import { EquipmentSkillForm, SkillFormData } from './EquipmentSkillForm';
import { toast } from '@/hooks/useToast';

export default function EquipmentSkillEditPage() {
    const { id } = useParams<{ id: string }>();
    const navigate = useNavigate();
    const queryClient = useQueryClient();

    const { data: skill, isLoading } = useQuery<EquipmentSkill>({
        queryKey: ['equipment-skills', id],
        queryFn: async () => {
            const response = await api.get(`/equipment-skills/${id}`);
            return response.data.data;
        },
    });

    const mutation = useMutation({
        mutationFn: async (data: SkillFormData) => {
            const response = await api.put(`/equipment-skills/${id}`, data);
            return response.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['equipment-skills'] });
            toast({
                title: '저장 완료',
                description: '장비 스킬이 저장되었습니다.',
            });
            navigate(-1);
        },
        onError: () => {
            toast({
                title: '저장 실패',
                description: '장비 스킬 저장에 실패했습니다.',
                variant: 'destructive',
            });
        },
    });

    if (isLoading) {
        return (
            <div className="flex h-64 items-center justify-center">
                <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent" />
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <PageHeader
                title={`스킬 수정: ${skill?.name || ''}`}
                description="장비 스킬 정보를 수정합니다."
                showBack
            />
            <EquipmentSkillForm
                skill={skill}
                onSubmit={(data) => mutation.mutate(data)}
                onCancel={() => navigate(-1)}
                isSubmitting={mutation.isPending}
            />
        </div>
    );
}
