import { Head } from "@inertiajs/react";

interface AboutProps {
  message: string;
}

export default function About({ message }: AboutProps) {
  return (
    <div className="p-10">
      <Head title="О нас 1" />
      <p className="text-sm text-muted-foreground">{message}</p>
    </div>
  );
}

About.layout = () => ({
    breadcrumbs: [
        {
            title: 'О проекте',
            href: '/about',
        },
    ],
});