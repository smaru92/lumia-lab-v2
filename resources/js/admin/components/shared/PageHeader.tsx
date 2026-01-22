import { ReactNode } from 'react';
import { useNavigate } from 'react-router-dom';
import { ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface PageHeaderProps {
    title: string;
    description?: string;
    showBack?: boolean;
    actions?: ReactNode;
}

export function PageHeader({ title, description, showBack, actions }: PageHeaderProps) {
    const navigate = useNavigate();

    return (
        <div className="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div className="flex items-center gap-4">
                {showBack && (
                    <Button variant="ghost" size="icon" onClick={() => navigate(-1)}>
                        <ArrowLeft className="h-5 w-5" />
                    </Button>
                )}
                <div>
                    <h1 className="text-2xl font-bold">{title}</h1>
                    {description && (
                        <p className="text-sm text-[hsl(var(--muted-foreground))]">{description}</p>
                    )}
                </div>
            </div>
            {actions && <div className="flex gap-2">{actions}</div>}
        </div>
    );
}
