import { Form } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import { useState } from 'react';
import OrgController from '@/actions/App/Http/Controllers/Orgs/OrgController';
import InputError from '@/components/input-error';
import { Button } from '@/shared/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/shared/ui/dialog';
import { Input } from '@/shared/ui/input';
import { Label } from '@/shared/ui/label';

export default function CreateOrgModal({ children }: PropsWithChildren) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{children}</DialogTrigger>
            <DialogContent>
                <Form
                    key={String(open)}
                    {...OrgController.store.form()}
                    className="space-y-6"
                    onSuccess={() => setOpen(false)}
                >
                    {({ errors, processing }) => (
                        <>
                            <DialogHeader>
                                <DialogTitle>
                                    Создать новую организацию
                                </DialogTitle>
                                <DialogDescription>
                                    Создайте организацию в текущей команде.
                                </DialogDescription>
                            </DialogHeader>

                            <div className="grid gap-2">
                                <Label htmlFor="org-name">
                                    Название организации
                                </Label>
                                <Input
                                    id="org-name"
                                    name="name"
                                    data-test="create-org-name"
                                    placeholder="Моя организация"
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary">Отмена</Button>
                                </DialogClose>

                                <Button
                                    type="submit"
                                    data-test="create-org-submit"
                                    disabled={processing}
                                >
                                    Создать организацию
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
