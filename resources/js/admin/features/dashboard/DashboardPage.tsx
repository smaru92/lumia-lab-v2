import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Users, Package, Sparkles, History } from 'lucide-react';
import { Link } from 'react-router-dom';

const stats = [
    {
        name: '캐릭터',
        href: '/characters',
        icon: Users,
        description: '게임 캐릭터 관리',
    },
    {
        name: '장비',
        href: '/equipment',
        icon: Package,
        description: '장비 아이템 관리',
    },
    {
        name: '장비 스킬',
        href: '/equipment-skills',
        icon: Sparkles,
        description: '장비 스킬 관리',
    },
    {
        name: '버전 히스토리',
        href: '/version-histories',
        icon: History,
        description: '패치 노트 및 버전 관리',
    },
];

export default function DashboardPage() {
    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-3xl font-bold">대시보드</h1>
                <p className="text-[hsl(var(--muted-foreground))]">
                    Lumia Lab 관리자 페이지에 오신 것을 환영합니다.
                </p>
            </div>

            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                {stats.map((stat) => (
                    <Link key={stat.name} to={stat.href}>
                        <Card className="transition-colors hover:bg-[hsl(var(--accent))]">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    {stat.name}
                                </CardTitle>
                                <stat.icon className="h-5 w-5 text-[hsl(var(--muted-foreground))]" />
                            </CardHeader>
                            <CardContent>
                                <p className="text-xs text-[hsl(var(--muted-foreground))]">
                                    {stat.description}
                                </p>
                            </CardContent>
                        </Card>
                    </Link>
                ))}
            </div>
        </div>
    );
}
