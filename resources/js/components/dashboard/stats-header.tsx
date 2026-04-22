type StatsHeaderProps = {
    userName: string;
};

export function StatsHeader({ userName }: StatsHeaderProps) {
    return (
        <div className="p-4">
            <h2 className="text-lg font-medium">Статистика</h2>
            <p className="mt-1 text-sm text-muted-foreground">Привет, {userName}!</p>
        </div>
    );
}
