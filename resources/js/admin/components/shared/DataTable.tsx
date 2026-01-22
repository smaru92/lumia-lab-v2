import {
    ColumnDef,
    flexRender,
    getCoreRowModel,
    useReactTable,
    getPaginationRowModel,
    SortingState,
    getSortedRowModel,
    ColumnFiltersState,
    getFilteredRowModel,
    Table as ReactTable,
    PaginationState,
} from '@tanstack/react-table';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useState, ReactNode, useMemo, useCallback } from 'react';
import { useSearchParams } from 'react-router-dom';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight, Search } from 'lucide-react';

interface FilterProps {
    filterValue: string;
    filterColumn: string;
    onFilterChange: (column: string, value: string) => void;
}

interface DataTableProps<TData, TValue> {
    columns: ColumnDef<TData, TValue>[];
    data: TData[];
    searchKey?: string;
    searchPlaceholder?: string;
    filters?: (table: ReactTable<TData>, filterProps: FilterProps) => ReactNode;
}

export function DataTable<TData, TValue>({
    columns,
    data,
    searchKey,
    searchPlaceholder = '검색...',
    filters,
}: DataTableProps<TData, TValue>) {
    const [searchParams, setSearchParams] = useSearchParams();
    const [sorting, setSorting] = useState<SortingState>([]);

    // URL에서 현재 값 읽기 (매 렌더마다)
    const urlPage = searchParams.get('page');
    const urlSearch = searchParams.get('search');
    const urlFilter = searchParams.get('filter');
    const urlFilterColumn = searchParams.get('filterColumn');

    // URL 기반으로 필터 상태 계산
    const columnFilters = useMemo((): ColumnFiltersState => {
        const filters: ColumnFiltersState = [];
        if (searchKey && urlSearch) {
            filters.push({ id: searchKey, value: urlSearch });
        }
        if (urlFilter && urlFilterColumn) {
            filters.push({ id: urlFilterColumn, value: urlFilter });
        }
        return filters;
    }, [searchKey, urlSearch, urlFilter, urlFilterColumn]);

    // URL 기반으로 페이지 상태 계산
    const pagination = useMemo((): PaginationState => ({
        pageIndex: urlPage ? Math.max(0, parseInt(urlPage, 10) - 1) : 0,
        pageSize: 10,
    }), [urlPage]);

    // URL 업데이트 함수
    const updateUrl = useCallback((updates: {
        search?: string;
        filter?: string;
        filterColumn?: string;
        page?: number;
    }) => {
        const params = new URLSearchParams(searchParams);

        // 검색어
        if (updates.search !== undefined) {
            if (updates.search) {
                params.set('search', updates.search);
            } else {
                params.delete('search');
            }
            // 검색 변경 시 페이지 초기화
            params.delete('page');
        }

        // 필터
        if (updates.filter !== undefined && updates.filterColumn !== undefined) {
            if (updates.filter && updates.filter !== 'all') {
                params.set('filter', updates.filter);
                params.set('filterColumn', updates.filterColumn);
            } else {
                params.delete('filter');
                params.delete('filterColumn');
            }
            // 필터 변경 시 페이지 초기화
            params.delete('page');
        }

        // 페이지
        if (updates.page !== undefined) {
            if (updates.page > 0) {
                params.set('page', String(updates.page + 1));
            } else {
                params.delete('page');
            }
        }

        setSearchParams(params, { replace: true });
    }, [searchParams, setSearchParams]);

    const table = useReactTable({
        data,
        columns,
        getCoreRowModel: getCoreRowModel(),
        getPaginationRowModel: getPaginationRowModel(),
        onSortingChange: setSorting,
        getSortedRowModel: getSortedRowModel(),
        getFilteredRowModel: getFilteredRowModel(),
        manualFiltering: false,
        state: {
            sorting,
            columnFilters,
            pagination,
        },
    });

    // 검색 변경 핸들러
    const handleSearchChange = (value: string) => {
        updateUrl({ search: value || undefined });
    };

    // 필터 변경 핸들러
    const handleFilterChange = useCallback((column: string, value: string) => {
        updateUrl({ filter: value, filterColumn: column });
    }, [updateUrl]);

    // 페이지 변경 핸들러
    const handlePageChange = (pageIndex: number) => {
        updateUrl({ page: pageIndex });
    };

    // 필터 props
    const filterProps: FilterProps = {
        filterValue: urlFilter ?? 'all',
        filterColumn: urlFilterColumn ?? '',
        onFilterChange: handleFilterChange,
    };

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-center gap-4">
                {searchKey && (
                    <div className="flex items-center gap-2">
                        <Search className="h-4 w-4 text-[hsl(var(--muted-foreground))]" />
                        <Input
                            placeholder={searchPlaceholder}
                            value={urlSearch ?? ''}
                            onChange={(event) => handleSearchChange(event.target.value)}
                            className="max-w-sm"
                        />
                    </div>
                )}
                {filters && filters(table, filterProps)}
            </div>

            <div className="rounded-md border border-[hsl(var(--border))]">
                <Table>
                    <TableHeader>
                        {table.getHeaderGroups().map((headerGroup) => (
                            <TableRow key={headerGroup.id}>
                                {headerGroup.headers.map((header) => (
                                    <TableHead key={header.id}>
                                        {header.isPlaceholder
                                            ? null
                                            : flexRender(
                                                  header.column.columnDef.header,
                                                  header.getContext()
                                              )}
                                    </TableHead>
                                ))}
                            </TableRow>
                        ))}
                    </TableHeader>
                    <TableBody>
                        {table.getRowModel().rows?.length ? (
                            table.getRowModel().rows.map((row) => (
                                <TableRow
                                    key={row.id}
                                    data-state={row.getIsSelected() && 'selected'}
                                >
                                    {row.getVisibleCells().map((cell) => (
                                        <TableCell key={cell.id}>
                                            {flexRender(
                                                cell.column.columnDef.cell,
                                                cell.getContext()
                                            )}
                                        </TableCell>
                                    ))}
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell
                                    colSpan={columns.length}
                                    className="h-24 text-center"
                                >
                                    데이터가 없습니다.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>

            <div className="flex items-center justify-between">
                <div className="text-sm text-[hsl(var(--muted-foreground))]">
                    총 {table.getFilteredRowModel().rows.length}개 중{' '}
                    {table.getFilteredRowModel().rows.length > 0
                        ? pagination.pageIndex * pagination.pageSize + 1
                        : 0}
                    -
                    {Math.min(
                        (pagination.pageIndex + 1) * pagination.pageSize,
                        table.getFilteredRowModel().rows.length
                    )}
                    개 표시
                </div>
                <div className="flex items-center space-x-2">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => handlePageChange(0)}
                        disabled={!table.getCanPreviousPage()}
                    >
                        <ChevronsLeft className="h-4 w-4" />
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => handlePageChange(pagination.pageIndex - 1)}
                        disabled={!table.getCanPreviousPage()}
                    >
                        <ChevronLeft className="h-4 w-4" />
                    </Button>
                    <span className="text-sm">
                        {pagination.pageIndex + 1} / {table.getPageCount() || 1}
                    </span>
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => handlePageChange(pagination.pageIndex + 1)}
                        disabled={!table.getCanNextPage()}
                    >
                        <ChevronRight className="h-4 w-4" />
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => handlePageChange(table.getPageCount() - 1)}
                        disabled={!table.getCanNextPage()}
                    >
                        <ChevronsRight className="h-4 w-4" />
                    </Button>
                </div>
            </div>
        </div>
    );
}
