import { JobCardData, JobMatchCard } from '@/components/job-match-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';

interface JobMatchData {
    job: JobCardData;
    score: number;
    matched_skills_count: number;
}

interface JobsPageProps {
    jobs: JobCardData[];
    matches: JobMatchData[];
    userSkills: string[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Jobs',
        href: '/jobs',
    },
];

export default function JobsPage({ jobs, matches, userSkills }: JobsPageProps) {
    const [search, setSearch] = useState('');

    const filteredMatches = useMemo(() => {
        const keyword = search.trim().toLowerCase();
        if (keyword === '') {
            return matches;
        }

        return matches.filter(({ job }) => {
            return [job.title, job.company ?? '', job.location ?? '', job.description].some((value) => value.toLowerCase().includes(keyword));
        });
    }, [matches, search]);

    const filteredJobs = useMemo(() => {
        const keyword = search.trim().toLowerCase();
        if (keyword === '') {
            return jobs;
        }

        return jobs.filter((job) => [job.title, job.company ?? '', job.location ?? '', job.description].some((value) => value.toLowerCase().includes(keyword)));
    }, [jobs, search]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Job Matches" />

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <Card className="border-amber-200 bg-gradient-to-r from-amber-50 via-white to-sky-50">
                    <CardHeader>
                        <CardTitle>Job Discovery</CardTitle>
                        <CardDescription>See matched roles from your profile skills and browse all available career jobs.</CardDescription>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex flex-wrap gap-2">
                            {userSkills.length > 0 ? (
                                userSkills.map((skill) => (
                                    <Badge key={skill} variant="outline">
                                        {skill}
                                    </Badge>
                                ))
                            ) : (
                                <Badge variant="outline">No profile skills yet</Badge>
                            )}
                        </div>
                        <div className="flex items-center gap-2">
                            <Input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Search title, company, location..." />
                            <Button variant="outline" onClick={() => router.get(route('jobs.index'))}>
                                Refresh
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <section className="space-y-3">
                    <h2 className="text-lg font-semibold">Top Matches ({filteredMatches.length})</h2>
                    {filteredMatches.length > 0 ? (
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            {filteredMatches.map((match) => (
                                <JobMatchCard
                                    key={`match-${match.job.id}`}
                                    job={match.job}
                                    score={match.score}
                                    matchedSkillsCount={match.matched_skills_count}
                                />
                            ))}
                        </div>
                    ) : (
                        <Card>
                            <CardContent className="p-6 text-sm text-muted-foreground">No matched jobs yet. Add profile skills and try again.</CardContent>
                        </Card>
                    )}
                </section>

                <section className="space-y-3">
                    <h2 className="text-lg font-semibold">All Jobs ({filteredJobs.length})</h2>
                    {filteredJobs.length > 0 ? (
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            {filteredJobs.map((job) => (
                                <JobMatchCard key={`job-${job.id}`} job={job} />
                            ))}
                        </div>
                    ) : (
                        <Card>
                            <CardContent className="p-6 text-sm text-muted-foreground">No jobs available in the database yet.</CardContent>
                        </Card>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
