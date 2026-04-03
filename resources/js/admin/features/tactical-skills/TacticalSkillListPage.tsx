import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { ColumnDef } from '@tanstack/react-table';
import { Pencil, Trash2, Plus } from 'lucide-react';
import api from '@/lib/axios';
import { TacticalSkill } from '@/types';
import { DataTable } from '@/components/shared/DataTable';
import { PageHeader } from '@/components/shared/PageHeader';
import { DeleteDialog } from '@/components/shared/DeleteDialog';
import { Button } from '@/components/ui/button';
import { toast } from '@/hooks/useToast';

export default function TacticalSkillListPage() {
    const [deleteId, setDeleteId] = useState<number | null>(null);
    const queryClient = useQueryClient();

    const { data: skills = [], isLoading } = useQuery<TacticalSkill[]>({
        queryKey: ['tactical-skills'],
        queryFn: async () => {
            const response = await api.get('/tactical-skills');
            return response.data.data;
        },
    });

    const deleteMutation = useMutation({
        mutationFn: async (id: number) => {
            await api.delete(`/tactical-skills/${id}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['tactical-skills'] });
            toast({ title: '삭제 완료', description: '전술스킬이 삭제되었습니다.' });
            setDeleteId(null);
        },
        onError: () => {
            toast({
                title: '삭제 실패',
                description: '전술스킬 삭제에 실패했습니다.',
                variant: 'destructive',
            });
        },
    });

    const columns: ColumnDef<TacticalSkill>[] = [
        { accessorKey: 'id', header: 'ID', size: 60 },
        { accessorKey: 'name', header: '이름' },
        {
            accessorKey: 'tooltip',
            header: '툴팁',
            cell: ({ row }) => {
                const t = row.original.tooltip;
                if (!t) return '-';
                return t.length > 80 ? t.slice(0, 80) + '...' : t;
            },
        },
        {
            id: 'actions',
            cell: ({ row }) => (
                <div className="flex items-center gap-1">
                    <Button variant="ghost" size="icon" asChild>
                        <Link to={`/tactical-skills/${row.original.id}/edit`}>
                            <Pencil className="h-4 w-4" />
                        </Link>
                    </Button>
                    <Button variant="ghost" size="icon" onClick={() => setDeleteId(row.original.id)}>
                        <Trash2 className="h-4 w-4 text-[hsl(var(--destructive))]" />
                    </Button>
                </div>
            ),
        },
    ];

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
                title="전술스킬"
                description="전술스킬 목록을 관리합니다."
                actions={
                    <Button asChild>
                        <Link to="/tactical-skills/create">
                            <Plus className="mr-2 h-4 w-4" />
                            새 전술스킬
                        </Link>
                    </Button>
                }
            />
            <DataTable
                columns={columns}
                data={skills}
                searchKey="name"
                searchPlaceholder="전술스킬 이름으로 검색..."
            />
            <DeleteDialog
                open={deleteId !== null}
                onOpenChange={(open) => !open && setDeleteId(null)}
                onConfirm={() => deleteId && deleteMutation.mutate(deleteId)}
                title="전술스킬 삭제"
                description="이 전술스킬을 삭제하시겠습니까?"
                isDeleting={deleteMutation.isPending}
            />
        </div>
    );
}
