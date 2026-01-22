import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { ColumnDef } from '@tanstack/react-table';
import { Pencil } from 'lucide-react';
import api from '@/lib/axios';
import { Character, CharacterTag } from '@/types';
import { DataTable } from '@/components/shared/DataTable';
import { PageHeader } from '@/components/shared/PageHeader';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const columns: ColumnDef<Character>[] = [
    {
        accessorKey: 'id',
        header: 'ID',
    },
    {
        accessorKey: 'name',
        header: '이름',
    },
    {
        id: 'tags',
        header: '태그',
        cell: ({ row }) => {
            const tags = row.original.tags;
            if (!tags || tags.length === 0) return '-';
            return (
                <div className="flex flex-wrap gap-1">
                    {tags.map((tag) => (
                        <Badge key={tag.id} variant="outline" className="text-xs">
                            {tag.name}
                        </Badge>
                    ))}
                </div>
            );
        },
        filterFn: (row, _id, filterValue) => {
            if (!filterValue) return true;
            const tags = row.original.tags;
            if (!tags || tags.length === 0) return false;
            return tags.some((tag) => tag.id === Number(filterValue));
        },
    },
    {
        accessorKey: 'max_hp',
        header: '최대 HP',
    },
    {
        accessorKey: 'attack_power',
        header: '공격력',
    },
    {
        accessorKey: 'deffence',
        header: '방어력',
    },
    {
        accessorKey: 'move_speed',
        header: '이동속도',
    },
    {
        id: 'actions',
        cell: ({ row }) => (
            <Button variant="ghost" size="icon" asChild>
                <Link to={`/characters/${row.original.id}/edit`}>
                    <Pencil className="h-4 w-4" />
                </Link>
            </Button>
        ),
    },
];

export default function CharacterListPage() {
    const { data: characters = [], isLoading } = useQuery<Character[]>({
        queryKey: ['characters'],
        queryFn: async () => {
            const response = await api.get('/characters');
            return response.data.data;
        },
    });

    const { data: allTags = [] } = useQuery<CharacterTag[]>({
        queryKey: ['character-tags'],
        queryFn: async () => {
            try {
                const response = await api.get('/character-tags');
                return response.data.data;
            } catch {
                return [];
            }
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
                title="캐릭터"
                description="게임 캐릭터 목록을 관리합니다."
            />
            <DataTable
                columns={columns}
                data={characters}
                searchKey="name"
                searchPlaceholder="캐릭터 이름으로 검색..."
                filters={(_table, { filterValue, onFilterChange }) => (
                    <Select
                        value={filterValue}
                        onValueChange={(value) => onFilterChange('tags', value)}
                    >
                        <SelectTrigger className="w-[180px]">
                            <SelectValue placeholder="태그 필터" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">전체 태그</SelectItem>
                            {allTags.map((tag) => (
                                <SelectItem key={tag.id} value={String(tag.id)}>
                                    {tag.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                )}
            />
        </div>
    );
}
