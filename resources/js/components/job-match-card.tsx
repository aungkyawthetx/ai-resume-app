import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

export interface JobCardData {
    id: number;
    title: string;
    company: string | null;
    description: string;
    location: string | null;
    skills: string[];
    salary: string | number | null;
}

interface JobMatchCardProps {
    job: JobCardData;
    score?: number;
    matchedSkillsCount?: number;
}

export function JobMatchCard({ job, score, matchedSkillsCount }: JobMatchCardProps) {
    return (
        <Card className="h-full">
            <CardHeader className="space-y-2">
                <div className="flex items-start justify-between gap-4">
                    <CardTitle className="text-lg">{job.title}</CardTitle>
                    {typeof score === 'number' && <Badge variant="secondary">Match {score}</Badge>}
                </div>
                <CardDescription>
                    {[job.company, job.location].filter(Boolean).join(' | ') || 'Company and location not provided'}
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
                <p className="line-clamp-4 text-sm text-muted-foreground">{job.description}</p>

                <div className="flex flex-wrap gap-2">
                    {job.skills.length > 0 ? (
                        job.skills.map((skill) => (
                            <Badge key={`${job.id}-${skill}`} variant="outline">
                                {skill}
                            </Badge>
                        ))
                    ) : (
                        <Badge variant="outline">No skills listed</Badge>
                    )}
                </div>

                <div className="flex items-center justify-between text-xs text-muted-foreground">
                    <span>{job.salary ? `Salary: $${job.salary}` : 'Salary not listed'}</span>
                    {typeof matchedSkillsCount === 'number' && <span>{matchedSkillsCount} skill matches</span>}
                </div>
            </CardContent>
        </Card>
    );
}
