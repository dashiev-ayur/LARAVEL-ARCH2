import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-white dark:bg-white dark:text-black">
                <AppLogoIcon className="size-5" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.3 truncate leading-tight text-[13px] font-semibold">
                    Сервисы для малого<br/>бизнеса
                </span>
            </div>
        </>
    );
}
