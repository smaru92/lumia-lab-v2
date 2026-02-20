import { useState, useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { ColumnDef } from '@tanstack/react-table';
import { Pencil } from 'lucide-react';
import api from '@/lib/axios';
import { Equipment } from '@/types';
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

const GRADE_CONFIG: Record<string, { label: string; className: string }> = {
    Common: { label: '일반', className: 'bg-gray-500 text-white' },
    Uncommon: { label: '고급', className: 'bg-green-500 text-white' },
    Rare: { label: '희귀', className: 'bg-blue-500 text-white' },
    Epic: { label: '영웅', className: 'bg-purple-500 text-white' },
    Legend: { label: '전설', className: 'bg-yellow-500 text-black' },
    Mythic: { label: '초월', className: 'bg-red-500 text-white' },
};

const GRADE_OPTIONS = [
    { value: 'all', label: '전체 등급' },
    { value: 'Common', label: '일반 (Common)' },
    { value: 'Uncommon', label: '고급 (Uncommon)' },
    { value: 'Rare', label: '희귀 (Rare)' },
    { value: 'Epic', label: '영웅 (Epic)' },
    { value: 'Legend', label: '전설 (Legend)' },
    { value: 'Mythic', label: '초월 (Mythic)' },
];

const TYPE3_CONFIG: Record<string, string> = {
    mt: '운석',
    tl: '생명의나무',
    mr: '미스릴',
    fc: '포스코어',
    vf: '혈액샘플',
};

const TYPE3_OPTIONS = [
    { value: 'all', label: '전체 재료' },
    { value: 'mt', label: '운석' },
    { value: 'tl', label: '생명의나무' },
    { value: 'mr', label: '미스릴' },
    { value: 'fc', label: '포스코어' },
    { value: 'vf', label: '혈액샘플' },
];

const columns: ColumnDef<Equipment>[] = [
    {
        accessorKey: 'id',
        header: 'ID',
    },
    {
        accessorKey: 'name',
        header: '이름',
    },
    {
        accessorKey: 'item_type1',
        header: '타입1',
    },
    {
        accessorKey: 'item_type2',
        header: '타입2',
    },
    {
        accessorKey: 'item_type3',
        header: '핵심 재료',
        cell: ({ row }) => {
            const type3 = row.original.item_type3;
            if (!type3) return '-';
            return TYPE3_CONFIG[type3] || type3;
        },
    },
    {
        accessorKey: 'item_grade',
        header: '등급',
        cell: ({ row }) => {
            const grade = row.original.item_grade;
            if (!grade) return '-';
            const config = GRADE_CONFIG[grade];
            return (
                <Badge className={`${config?.className || ''} whitespace-nowrap`}>
                    {config?.label || grade}
                </Badge>
            );
        },
    },
    {
        accessorKey: 'attack_power',
        header: '공격력',
    },
    {
        accessorKey: 'defense',
        header: '방어력',
    },
    {
        id: 'actions',
        cell: ({ row }) => (
            <Button variant="ghost" size="icon" asChild>
                <Link to={`/equipment/${row.original.id}/edit`}>
                    <Pencil className="h-4 w-4" />
                </Link>
            </Button>
        ),
    },
];

export default function EquipmentListPage() {
    const [type3Filter, setType3Filter] = useState('all');

    const { data: equipment = [], isLoading } = useQuery<Equipment[]>({
        queryKey: ['equipment'],
        queryFn: async () => {
            const response = await api.get('/equipment');
            return response.data.data;
        },
    });

    const filteredEquipment = useMemo(() => {
        if (type3Filter === 'all') return equipment;
        return equipment.filter(item => item.item_type3 === type3Filter);
    }, [equipment, type3Filter]);

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
                title="장비"
                description="장비 아이템 목록을 관리합니다."
            />
            <DataTable
                columns={columns}
                data={filteredEquipment}
                searchKey="name"
                searchPlaceholder="장비 이름으로 검색..."
                filters={(_table, { filterValue, onFilterChange }) => (
                    <div className="flex gap-2">
                        <Select
                            value={filterValue}
                            onValueChange={(value) => onFilterChange('item_grade', value)}
                        >
                            <SelectTrigger className="w-[150px]">
                                <SelectValue placeholder="등급 필터" />
                            </SelectTrigger>
                            <SelectContent>
                                {GRADE_OPTIONS.map((option) => (
                                    <SelectItem key={option.value} value={option.value}>
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Select
                            value={type3Filter}
                            onValueChange={setType3Filter}
                        >
                            <SelectTrigger className="w-[150px]">
                                <SelectValue placeholder="재료 필터" />
                            </SelectTrigger>
                            <SelectContent>
                                {TYPE3_OPTIONS.map((option) => (
                                    <SelectItem key={option.value} value={option.value}>
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                )}
            />
        </div>
    );
}
