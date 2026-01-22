import { ReactNode } from 'react';
import { Link } from 'react-router-dom';
import { ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface PageHeaderProps {
    title: string;
    description?: string;
    backLink?: string;
    actions?: ReactNode;
}

export function PageHeader({ title, description, backLink, actions }: PageHeaderProps) {
    return (
        <div className="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div className="flex items-center gap-4">
                {backLink && (
                    <Button variant="ghost" size="icon" asChild>
                        <Link to={backLink}>
                            <ArrowLeft className="h-5 w-5" />
                        </Link>
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
