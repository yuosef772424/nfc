<footer class="bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800 mt-auto">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <!-- الصف العلوي: الأقسام -->
        <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 md:grid-cols-4">
            <!-- القسم 1: عن النظام -->
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">نظام NFC المالي</h3>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    منصة متكاملة للمدفوعات الرقمية وإدارة المحافظ الإلكترونية عبر تقنية NFC.
                </p>
                <div class="mt-4 flex space-x-4 rtl:space-x-reverse">
                    <a href="#" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                        <span class="sr-only">Facebook</span>
                        <x-filament::icon icon="heroicon-o-globe-alt" class="h-5 w-5" />
                    </a>
                    <a href="#" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                        <span class="sr-only">Twitter</span>
                        <x-filament::icon icon="heroicon-o-chat-bubble-left-right" class="h-5 w-5" />
                    </a>
                    <a href="#" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                        <span class="sr-only">LinkedIn</span>
                        <x-filament::icon icon="heroicon-o-building-office" class="h-5 w-5" />
                    </a>
                </div>
            </div>

            <!-- القسم 2: روابط سريعة -->
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">روابط سريعة</h3>
                <ul class="mt-2 space-y-2 text-xs">
                    <li><a href="#" class="text-gray-500 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400">الرئيسية</a></li>
                    <li><a href="#" class="text-gray-500 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400">الخدمات</a></li>
                    <li><a href="#" class="text-gray-500 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400">الأسئلة الشائعة</a></li>
                    <li><a href="#" class="text-gray-500 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400">سياسة الخصوصية</a></li>
                </ul>
            </div>

            <!-- القسم 3: الدعم -->
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">الدعم والمساعدة</h3>
                <ul class="mt-2 space-y-2 text-xs">
                    <li><a href="#" class="text-gray-500 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400">مركز المساعدة</a></li>
                    <li><a href="#" class="text-gray-500 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400">تواصل معنا</a></li>
                    <li><a href="#" class="text-gray-500 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400">الإبلاغ عن مشكلة</a></li>
                    <li><a href="#" class="text-gray-500 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400">طلب ميزة جديدة</a></li>
                </ul>
            </div>

            <!-- القسم 4: معلومات التواصل -->
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">تواصل معنا</h3>
                <ul class="mt-2 space-y-2 text-xs">
                    <li class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                        <x-filament::icon icon="heroicon-o-envelope" class="h-4 w-4" />
                        <span>support@nfc-pay.com</span>
                    </li>
                    <li class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                        <x-filament::icon icon="heroicon-o-phone" class="h-4 w-4" />
                        <span>+967 1 234 567</span>
                    </li>
                    <li class="flex items-center gap-2 text-gray-500 dark:text-gray-400">
                        <x-filament::icon icon="heroicon-o-map-pin" class="h-4 w-4" />
                        <span>صنعاء، اليمن - شارع الزبيري</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- الصف السفلي: حقوق النشر -->
        <div class="mt-8 border-t border-gray-200 dark:border-gray-800 pt-6 text-center">
            <p class="text-xs text-gray-400 dark:text-gray-500">
                &copy; {{ date('Y') }} نظام NFC المالي. جميع الحقوق محفوظة.
                <span class="mx-2">|</span>
                تم التطوير بواسطة <a href="#" class="hover:text-primary-500">فريق NFC</a>
            </p>
        </div>
    </div>
</footer>