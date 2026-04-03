import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { ColumnDef } from '@tanstack/react-table';
import { Pencil, Trash2, Plus } from 'lucide-react';
import api from '@/lib/axios';
import { GameTrait } from '@/types';
import { DataTable } from '@/components/shared/DataTable';
import { PageHeader } from '@/components/shared/PageHeader';
import { DeleteDialog } from '@/components/shared/DeleteDialog';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { toast } from '@/hooks/useToast';

export default function TraitListPage() {
    const [deleteId, setDeleteId] = useState<number | null>(null);
    const queryClient = useQueryClient();

    const { data: traits = [], isLoading } = useQuery<GameTrait[]>({
        queryKey: ['traits'],
        queryFn: async () => {
            const response = await api.get('/traits');
            return response.data.data;
        },
    });

    const deleteMutation = useMutation({
        mutationFn: async (id: number) => {
            await api.delete(`/traits/${id}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['traits'] });
            toast({ title: '삭제 완료', description: '특성이 삭제되었습니다.' });
            setDeleteId(null);
        },
        onError: () => {
            toast({
                title: '삭제 실패',
                description: '특성 삭제에 실패했습니다.',
                variant: 'destructive',
            });
        },
    });

    const columns: ColumnDef<GameTrait>[] = [
        { accessorKey: 'id', header: 'ID', size: 60 },
        { accessorKey: 'name', header: '이름' },
        {
            accessorKey: 'is_main',
            header: '구분',
            size: 90,
            cell: ({ row }) => {
                const v = row.original.is_main;
                if (v === null || v === undefined) return '-';
                return v === 1
                    ? <Badge className="bg-yellow-500 text-black whitespace-nowrap">메인</Badge>
                    : <Badge variant="outline" className="whitespace-nowrap">서브</Badge>;
            },
        },
        { accessorKey: 'category', header: '카테고리', cell: ({ row }) => row.original.category || '-' },
        {
            accessorKey: 'tooltip',
            header: '툴팁',
            cell: ({ row }) => {
                const t = row.original.tooltip;
                if (!t) return '-';
                return t.length > 60 ? t.slice(0, 60) + '...' : t;
            },
        },
        {
            id: 'actions',
            cell: ({ row }) => (
                <div className="flex items-center gap-1">
                    <Button variant="ghost" size="icon" asChild>
                        <Link to={`/traits/${row.original.id}/edit`}>
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
                title="특성"
                description="특성 목록을 관리합니다."
                actions={
                    <Button asChild>
                        <Link to="/traits/create">
                            <Plus className="mr-2 h-4 w-4" />
                            새 특성
                        </Link>
                    </Button>
                }
            />
            <DataTable
                columns={columns}
                data={traits}
                searchKey="name"
                searchPlaceholder="특성 이름으로 검색..."
            />
            <DeleteDialog
                open={deleteId !== null}
                onOpenChange={(open) => !open && setDeleteId(null)}
                onConfirm={() => deleteId && deleteMutation.mutate(deleteId)}
                title="특성 삭제"
                description="이 특성을 삭제하시겠습니까?"
                isDeleting={deleteMutation.isPending}
            />
        </div>
    );
}
