import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Sparkles } from 'lucide-react';
import { FormEvent } from 'react';

interface ResumeBuilderCardProps {
    targetRole: string;
    onTargetRoleChange: (value: string) => void;
    onSubmit: (event: FormEvent<HTMLFormElement>) => void;
    processing: boolean;
    error?: string;
}

export function ResumeBuilderCard({ targetRole, onTargetRoleChange, onSubmit, processing, error }: ResumeBuilderCardProps) {
    return (
        <Card className="border-sky-200/80 bg-gradient-to-br from-sky-50 to-white">
            <CardHeader>
                <CardTitle className="flex items-center gap-2 text-xl">
                    <Sparkles className="size-5 text-sky-600" />
                    AI Resume Builder
                </CardTitle>
                <CardDescription>Generate a polished resume from your profile details and selected role.</CardDescription>
            </CardHeader>
            <CardContent>
                <form onSubmit={onSubmit} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="target_role">Target role</Label>
                        <Input
                            id="target_role"
                            value={targetRole}
                            onChange={(event) => onTargetRoleChange(event.target.value)}
                            placeholder="e.g. Senior Frontend Developer"
                            maxLength={120}
                        />
                        <InputError message={error} />
                    </div>
                    <Button disabled={processing} className="w-full">
                        {processing ? 'Generating...' : 'Generate Resume'}
                    </Button>
                </form>
            </CardContent>
        </Card>
    );
}
