import { Link, useLocation } from 'react-router-dom';
import {
    Users,
    Package,
    Sparkles,
    History,
    LayoutDashboard,
    X,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';

interface SidebarProps {
    open: boolean;
    onClose: () => void;
}

const navigation = [
    { name: '대시보드', href: '/', icon: LayoutDashboard },
    { name: '캐릭터', href: '/characters', icon: Users },
    { name: '장비', href: '/equipment', icon: Package },
    { name: '장비 스킬', href: '/equipment-skills', icon: Sparkles },
    { name: '버전 히스토리', href: '/version-histories', icon: History },
];

export default function Sidebar({ open, onClose }: SidebarProps) {
    const location = useLocation();

    return (
        <>
            {open && (
                <div
                    className="fixed inset-0 z-40 bg-black/50 lg:hidden"
                    onClick={onClose}
                />
            )}

            <aside
                className={cn(
                    'fixed inset-y-0 left-0 z-50 w-64 transform bg-[hsl(var(--card))] transition-transform duration-200 ease-in-out lg:static lg:translate-x-0',
                    open ? 'translate-x-0' : '-translate-x-full'
                )}
            >
                <div className="flex h-full flex-col">
                    <div className="flex h-16 items-center justify-between border-b border-[hsl(var(--border))] px-4">
                        <Link to="/" className="text-xl font-bold text-[hsl(var(--primary))]">
                            Lumia Lab Admin
                        </Link>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="lg:hidden"
                            onClick={onClose}
                        >
                            <X className="h-5 w-5" />
                        </Button>
                    </div>

                    <nav className="flex-1 space-y-1 p-4">
                        {navigation.map((item) => {
                            const isActive =
                                item.href === '/'
                                    ? location.pathname === '/'
                                    : location.pathname.startsWith(item.href);

                            return (
                                <Link
                                    key={item.name}
                                    to={item.href}
                                    onClick={onClose}
                                    className={cn(
                                        'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                                        isActive
                                            ? 'bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))]'
                                            : 'text-[hsl(var(--muted-foreground))] hover:bg-[hsl(var(--accent))] hover:text-[hsl(var(--accent-foreground))]'
                                    )}
                                >
                                    <item.icon className="h-5 w-5" />
                                    {item.name}
                                </Link>
                            );
                        })}
                    </nav>
                </div>
            </aside>
        </>
    );
}
