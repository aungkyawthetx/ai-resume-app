import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { BriefcaseBusiness, FileText, Gauge, Sparkles } from 'lucide-react';

interface DashboardStats {
    resumes_count: number;
    jobs_count: number;
    skills_count: number;
    matched_jobs_count: number;
    profile_completion_percent: number;
}

interface LatestResume {
    id: number;
    created_at: string | null;
    pdf_path: string | null;
}

interface ProfileChecklist {
    education: boolean;
    experience: boolean;
    skills: boolean;
}

interface TopMatch {
    id: number;
    title: string;
    company: string | null;
    location: string | null;
    matched_skills_count: number;
    created_at: string | null;
}

interface DashboardProps {
    stats: DashboardStats;
    latestResume: LatestResume | null;
    profileChecklist: ProfileChecklist;
    topMatches: TopMatch[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

export default function Dashboard({ stats, latestResume, profileChecklist, topMatches }: DashboardProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <Card className="border-sky-200 bg-gradient-to-r from-sky-50 via-white to-emerald-50">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-xl">
                            <Sparkles className="size-5 text-sky-600" />
                            AI Resume Overview
                        </CardTitle>
                        <CardDescription>Track your profile readiness and resume-job matching at a glance.</CardDescription>
                    </CardHeader>
                    <CardContent className="flex flex-wrap items-center justify-between gap-3">
                        <div className="text-sm text-muted-foreground">Profile completion: {stats.profile_completion_percent}%</div>
                        <div className="flex flex-wrap gap-2">
                            <Button asChild variant="outline">
                                <Link href="/settings/profile">Update Profile</Link>
                            </Button>
                            <Button asChild>
                                <Link href="/resume">Generate Resume</Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Total Resumes</CardDescription>
                            <CardTitle className="text-3xl">{stats.resumes_count}</CardTitle>
                        </CardHeader>
                        <CardContent className="text-xs text-muted-foreground">Generated resumes in your account.</CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Matched Jobs</CardDescription>
                            <CardTitle className="text-3xl">{stats.matched_jobs_count}</CardTitle>
                        </CardHeader>
                        <CardContent className="text-xs text-muted-foreground">Roles with overlapping skills.</CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Profile Skills</CardDescription>
                            <CardTitle className="text-3xl">{stats.skills_count}</CardTitle>
                        </CardHeader>
                        <CardContent className="text-xs text-muted-foreground">Skills saved in your profile.</CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Jobs Available</CardDescription>
                            <CardTitle className="text-3xl">{stats.jobs_count}</CardTitle>
                        </CardHeader>
                        <CardContent className="text-xs text-muted-foreground">Career job records in the system.</CardContent>
                    </Card>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-lg">
                                <Gauge className="size-5 text-amber-600" />
                                Profile Checklist
                            </CardTitle>
                            <CardDescription>Complete these to improve resume and job matching quality.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="flex items-center justify-between">
                                <span className="text-sm">Education added</span>
                                <Badge variant={profileChecklist.education ? 'default' : 'outline'}>
                                    {profileChecklist.education ? 'Done' : 'Missing'}
                                </Badge>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-sm">Experience added</span>
                                <Badge variant={profileChecklist.experience ? 'default' : 'outline'}>
                                    {profileChecklist.experience ? 'Done' : 'Missing'}
                                </Badge>
                            </div>
                            <div className="flex items-center justify-between">
                                <span className="text-sm">Skills added</span>
                                <Badge variant={profileChecklist.skills ? 'default' : 'outline'}>
                                    {profileChecklist.skills ? 'Done' : 'Missing'}
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-lg">
                                <FileText className="size-5 text-emerald-600" />
                                Latest Resume
                            </CardTitle>
                            <CardDescription>Your most recent generated resume snapshot.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {latestResume ? (
                                <>
                                    <div className="text-sm text-muted-foreground">
                                        Last generated: {latestResume.created_at ?? 'Unknown time'}
                                    </div>
                                    <div className="flex gap-2">
                                        <Button asChild variant="outline">
                                            <Link href="/resume">Open Resume Page</Link>
                                        </Button>
                                        <Button asChild>
                                            <a href={route('resume.download', latestResume.id)}>Download</a>
                                        </Button>
                                    </div>
                                </>
                            ) : (
                                <div className="text-sm text-muted-foreground">No resume generated yet.</div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-lg">
                            <BriefcaseBusiness className="size-5 text-sky-600" />
                            Top Skill Matches
                        </CardTitle>
                        <CardDescription>Highest matching roles based on your current profile skills.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {topMatches.length > 0 ? (
                            <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                {topMatches.map((match) => (
                                    <div key={match.id} className="rounded-lg border p-4">
                                        <div className="font-medium">{match.title}</div>
                                        <div className="text-sm text-muted-foreground">
                                            {[match.company, match.location].filter(Boolean).join(' • ') || 'Details unavailable'}
                                        </div>
                                        <div className="mt-3">
                                            <Badge variant="secondary">{match.matched_skills_count} skill match(es)</Badge>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="text-sm text-muted-foreground">
                                No matched jobs yet. Add more skills in profile to improve matching.
                            </div>
                        )}
                    </CardContent>
                </Card>

                <div className="flex justify-end">
                    <Button asChild variant="outline">
                        <Link href="/jobs">View All Jobs</Link>
                    </Button>
                </div>
            </div>
        </AppLayout>
    );
}
