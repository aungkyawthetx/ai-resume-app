import { ResumeBuilderCard } from '@/components/resume-builder-card';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';

interface ResumeData {
    id: number;
    content: string;
    pdf_path: string | null;
    created_at: string | null;
}

interface ResumePageProps {
    latestResume: ResumeData | null;
    generated: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Resume',
        href: '/resume',
    },
];

export default function ResumePage({ latestResume, generated }: ResumePageProps) {
    const { data, setData, post, processing, errors } = useForm({
        target_role: '',
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Resume Builder" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                {generated && (
                    <Alert className="border-emerald-200 bg-emerald-50 text-emerald-900">
                        <AlertTitle>Resume generated</AlertTitle>
                        <AlertDescription>Your latest resume is ready below.</AlertDescription>
                    </Alert>
                )}

                <div className="grid gap-4 lg:grid-cols-3">
                    <div className="lg:col-span-1">
                        <ResumeBuilderCard
                            targetRole={data.target_role}
                            onTargetRoleChange={(value) => setData('target_role', value)}
                            onSubmit={(event) => {
                                event.preventDefault();
                                post(route('resume.generate'));
                            }}
                            processing={processing}
                            error={errors.target_role}
                        />
                    </div>

                    <div className="lg:col-span-2">
                        <Card className="h-full">
                            <CardHeader className="space-y-2">
                                <CardTitle>Latest Resume</CardTitle>
                                <CardDescription>Preview, copy, and download your most recent generated resume.</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {latestResume ? (
                                    <>
                                        <div className="max-h-[460px] overflow-y-auto rounded-md border bg-muted/30 p-4">
                                            <pre className="whitespace-pre-wrap text-sm leading-6">{latestResume.content}</pre>
                                        </div>
                                        <div className="flex flex-wrap items-center gap-3">
                                            <Button asChild variant="outline">
                                                <a href={route('resume.download', latestResume.id)}>Download Resume</a>
                                            </Button>
                                            {latestResume.created_at && (
                                                <span className="text-xs text-muted-foreground">Generated at {latestResume.created_at}</span>
                                            )}
                                        </div>
                                    </>
                                ) : (
                                    <div className="rounded-md border border-dashed p-6 text-sm text-muted-foreground">
                                        No resume generated yet. Use the AI Resume Builder to create your first version.
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
