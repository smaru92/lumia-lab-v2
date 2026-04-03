import { useNavigate } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/axios';
import { PageHeader } from '@/components/shared/PageHeader';
import { TraitForm, TraitFormData } from './TraitForm';
import { toast } from '@/hooks/useToast';

export default function TraitCreatePage() {
    const navigate = useNavigate();
    const queryClient = useQueryClient();

    const mutation = useMutation({
        mutationFn: async (data: TraitFormData) => {
            const response = await api.post('/traits', data);
            return response.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['traits'] });
            toast({ title: '생성 완료', description: '새 특성이 생성되었습니다.' });
            navigate(-1);
        },
        onError: () => {
            toast({ title: '생성 실패', description: '특성 생성에 실패했습니다.', variant: 'destructive' });
        },
    });

    return (
        <div className="space-y-6">
            <PageHeader title="새 특성" description="새로운 특성을 생성합니다." showBack />
            <TraitForm
                onSubmit={(data) => mutation.mutate(data)}
                onCancel={() => navigate(-1)}
                isSubmitting={mutation.isPending}
            />
        </div>
    );
}
