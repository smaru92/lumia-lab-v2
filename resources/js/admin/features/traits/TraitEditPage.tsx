import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/axios';
import { GameTrait } from '@/types';
import { PageHeader } from '@/components/shared/PageHeader';
import { TraitForm, TraitFormData } from './TraitForm';
import { toast } from '@/hooks/useToast';

export default function TraitEditPage() {
    const { id } = useParams<{ id: string }>();
    const navigate = useNavigate();
    const queryClient = useQueryClient();

    const { data: trait, isLoading } = useQuery<GameTrait>({
        queryKey: ['traits', id],
        queryFn: async () => {
            const response = await api.get(`/traits/${id}`);
            return response.data.data;
        },
    });

    const mutation = useMutation({
        mutationFn: async (data: TraitFormData) => {
            const response = await api.put(`/traits/${id}`, data);
            return response.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['traits'] });
            toast({ title: '저장 완료', description: '특성이 저장되었습니다.' });
            navigate(-1);
        },
        onError: () => {
            toast({ title: '저장 실패', description: '특성 저장에 실패했습니다.', variant: 'destructive' });
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
                title={`특성 수정: ${trait?.name || ''}`}
                description="특성 정보를 수정합니다."
                showBack
            />
            <TraitForm
                trait={trait}
                onSubmit={(data) => mutation.mutate(data)}
                onCancel={() => navigate(-1)}
                isSubmitting={mutation.isPending}
            />
        </div>
    );
}
