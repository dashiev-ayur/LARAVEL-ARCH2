export default function PublicLayout({
    children,
}: {
    children: React.ReactNode;
}) {
    return (
        <div className="min-h-screen bg-background text-foreground">
            <header className="border-b px-6 py-4">Public header</header>
            <main className="mx-auto max-w-5xl p-6">{children}</main>
        </div>
    );
}