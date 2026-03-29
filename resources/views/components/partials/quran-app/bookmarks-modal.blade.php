<div
    class="quran-manager-shell"
    data-no-swipe
>
    <div class="quran-manager-toolbar">
        <p class="quran-manager-toolbar-note">
            انقر على الصفحة للانتقال، عدّل العنوان مباشرة، أو استبدلها بالصفحة الحالية.
        </p>
    </div>

    <div class="quran-manager-table-shell">
        <table class="quran-manager-table">
            <thead>
                <tr>
                    <th>الصفحة</th>
                    <th>ملاحظة</th>
                    <th>إجراءات</th>
                </tr>
            </thead>
            <tbody x-ref="bookmarksRowsList">
                <template x-if="bookmarks.length === 0">
                    <tr>
                        <td
                            class="quran-manager-empty"
                            colspan="3"
                        >لا توجد علامات محفوظة.</td>
                    </tr>
                </template>

                <template
                    x-for="bookmark in bookmarks"
                    :key="`quran-bookmark-entry-${bookmark.id}`"
                >
                    <tr
                        data-quran-bookmark-row
                        x-bind:class="bookmarkRowEffectClass(bookmark)"
                    >
                        <td>
                            <button
                                class="quran-manager-link"
                                data-quran-bookmark-go
                                type="button"
                                x-on:click="goToBookmark(bookmark)"
                                x-text="`${bookmark.page_number}`"
                            ></button>
                        </td>
                        <td>
                            <input
                                class="quran-manager-input"
                                data-quran-bookmark-title
                                type="text"
                                placeholder="-"
                                x-bind:value="bookmark.title ?? ''"
                                x-on:input.debounce.350ms="updateBookmarkTitle(bookmark.id, $event.target.value)"
                            />
                        </td>
                        <td>
                            <div class="quran-manager-actions">
                                <button
                                    class="quran-manager-action-button"
                                    data-quran-bookmark-replace
                                    type="button"
                                    x-on:click="replaceBookmarkPage(bookmark.id)"
                                >
                                    استبدال
                                </button>
                                <button
                                    class="quran-manager-action-button quran-manager-action-button--danger"
                                    data-quran-bookmark-remove
                                    type="button"
                                    x-on:click="removeBookmark(bookmark.id)"
                                >
                                    حذف
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>
