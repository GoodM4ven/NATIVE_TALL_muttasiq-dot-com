<div
    class="quran-manager-shell"
    data-no-swipe
    dir="rtl"
>
    <div class="quran-manager-toolbar">
        <p class="quran-manager-toolbar-note">
            آخر 100 انتقال غير معلّم، مع الاحتفاظ بجميع العناصر التي تمت إضافة وسوم لها.
        </p>

        <button
            class="quran-manager-toolbar-button"
            data-quran-history-clear
            type="button"
            x-bind:disabled="navigationHistory.length === 0"
            x-on:click="clearNavigationHistory()"
        >
            مسح غير المعلّم
        </button>
    </div>

    <div class="quran-manager-table-shell">
        <table class="quran-manager-table">
            <thead>
                <tr>
                    <th>الانتقال</th>
                    <th>التفاصيل</th>
                    <th>النوع</th>
                    <th>الوسوم</th>
                </tr>
            </thead>
            <tbody>
                <template x-if="navigationHistory.length === 0">
                    <tr>
                        <td
                            class="quran-manager-empty"
                            colspan="4"
                        >لا توجد عناصر بعد.</td>
                    </tr>
                </template>

                <template
                    x-for="entry in navigationHistory"
                    :key="`quran-history-entry-${entry.id}`"
                >
                    <tr data-quran-history-row>
                        <td>
                            <button
                                class="quran-manager-link"
                                data-quran-history-go
                                type="button"
                                x-on:click="goToHistoryEntry(entry)"
                                x-text="`صفحة ${entry.page_number}`"
                            ></button>
                        </td>
                        <td x-text="historyEntryContextLabel(entry)"></td>
                        <td x-text="historyEntrySourceLabel(entry)"></td>
                        <td>
                            <input
                                class="quran-manager-input"
                                data-quran-history-tags
                                type="text"
                                placeholder="وسوم مفصولة بفاصلة"
                                x-bind:value="historyEntryTagsAsText(entry)"
                                x-on:input.debounce.450ms="updateHistoryEntryTags(entry.id, $event.target.value)"
                            />
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>
