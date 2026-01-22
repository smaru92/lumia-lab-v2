import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { ColumnDef } from '@tanstack/react-table';
import { Pencil, Trash2, Plus } from 'lucide-react';
import api from '@/lib/axios';
import { EquipmentSkill } from '@/types';
import { DataTable } from '@/components/shared/DataTable';
import { PageHeader } from '@/components/shared/PageHeader';
import { DeleteDialog } from '@/components/shared/DeleteDialog';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { toast } from '@/hooks/useToast';

export default function EquipmentSkillListPage() {
    const [deleteId, setDeleteId] = useState<number | null>(null);
    const queryClient = useQueryClient();

    const { data: skills = [], isLoading } = useQuery<EquipmentSkill[]>({
        queryKey: ['equipment-skills'],
        queryFn: async () => {
            const response = await api.get('/equipment-skills');
            return response.data.data;
        },
    });

    const deleteMutation = useMutation({
        mutationFn: async (id: number) => {
            await api.delete(`/equipment-skills/${id}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['equipment-skills'] });
            toast({
                title: '삭제 완료',
                description: '장비 스킬이 삭제되었습니다.',
            });
            setDeleteId(null);
        },
        onError: () => {
            toast({
                title: '삭제 실패',
                description: '장비 스킬 삭제에 실패했습니다.',
                variant: 'destructive',
            });
        },
    });

    const columns: ColumnDef<EquipmentSkill>[] = [
        {
            accessorKey: 'id',
            header: 'ID',
        },
        {
            accessorKey: 'name',
            header: '이름',
        },
        {
            accessorKey: 'grade',
            header: '등급',
            cell: ({ row }) => {
                const grade = row.original.grade;
                if (!grade) return '-';
                return <Badge variant="secondary">{grade}</Badge>;
            },
        },
        {
            accessorKey: 'sub_category',
            header: '서브 카테고리',
        },
        {
            accessorKey: 'description',
            header: '설명',
            cell: ({ row }) => {
                const desc = row.original.description;
                if (!desc) return '-';
                return desc.length > 50 ? desc.slice(0, 50) + '...' : desc;
            },
        },
        {
            id: 'actions',
            cell: ({ row }) => (
                <div className="flex items-center gap-1">
                    <Button variant="ghost" size="icon" asChild>
                        <Link to={`/equipment-skills/${row.original.id}/edit`}>
                            <Pencil className="h-4 w-4" />
                        </Link>
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => setDeleteId(row.original.id)}
                    >
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
                title="장비 스킬"
                description="장비 스킬 목록을 관리합니다."
                actions={
                    <Button asChild>
                        <Link to="/equipment-skills/create">
                            <Plus className="mr-2 h-4 w-4" />
                            새 스킬
                        </Link>
                    </Button>
                }
            />
            <DataTable
                columns={columns}
                data={skills}
                searchKey="name"
                searchPlaceholder="스킬 이름으로 검색..."
            />

            <DeleteDialog
                open={deleteId !== null}
                onOpenChange={(open) => !open && setDeleteId(null)}
                onConfirm={() => deleteId && deleteMutation.mutate(deleteId)}
                title="스킬 삭제"
                description="이 장비 스킬을 삭제하시겠습니까? 연결된 장비에서도 제거됩니다."
                isDeleting={deleteMutation.isPending}
            />
        </div>
    );
}
