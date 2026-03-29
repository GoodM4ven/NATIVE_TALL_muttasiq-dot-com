<div
    class="quran-manager-shell"
    data-no-swipe
>
    <div class="quran-manager-toolbar">
        <p class="quran-manager-toolbar-note">
            {{ app_arabic_text('انقر على الصفحة للانتقال، عدّل الملاحظة مباشرة، واستعمل الوسوم المشتركة مع سجل التنقّل.') }}
        </p>
    </div>

    <div class="quran-manager-table-shell">
        <table class="quran-manager-table">
            <thead>
                <tr>
                    <th>{{ app_arabic_text('الصفحة') }}</th>
                    <th>{{ app_arabic_text('ملاحظة') }}</th>
                    <th>{{ app_arabic_text('الوسوم') }}</th>
                    <th>{{ app_arabic_text('إجراءات') }}</th>
                </tr>
            </thead>
            <tbody x-ref="bookmarksRowsList">
                <template x-if="bookmarks.length === 0">
                    <tr>
                        <td
                            class="quran-manager-empty"
                            colspan="4"
                        >{{ app_arabic_text('لا توجد علامات محفوظة.') }}</td>
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
                                data-quran-bookmark-note
                                type="text"
                                placeholder="-"
                                x-bind:value="bookmark.note ?? ''"
                                x-on:input.debounce.350ms="updateBookmarkNote(bookmark.id, $event.target.value)"
                            />
                        </td>
                        <td>
                            <div class="quran-manager-tags-field">
                                <template
                                    x-for="tag in (Array.isArray(bookmark.tags) ? bookmark.tags : [])"
                                    :key="`quran-bookmark-tag-${bookmark.id}-${tag}`"
                                >
                                    <span class="quran-manager-tag-chip">
                                        <span x-text="tag"></span>
                                        <button
                                            class="quran-manager-tag-chip-remove"
                                            type="button"
                                            aria-label="{{ app_arabic_text('حذف الوسم') }}"
                                            x-on:click.stop.prevent="removeBookmarkTag(bookmark.id, tag)"
                                        >
                                            ×
                                        </button>
                                    </span>
                                </template>

                                <input
                                    class="quran-manager-tags-entry outline-none"
                                    data-quran-bookmark-tags
                                    type="text"
                                    placeholder="{{ app_arabic_text('أضف وسمًا...') }}"
                                    x-bind:list="`quran-bookmark-tags-suggestions-${bookmark.id}`"
                                    x-bind:value="bookmarkTagDraft(bookmark.id)"
                                    x-on:input="setBookmarkTagDraft(bookmark.id, $event.target.value)"
                                    x-on:keydown="
                                        if (['Enter', 'Tab', ','].includes($event.key)) {
                                            $event.preventDefault();
                                            commitBookmarkTagDraft(bookmark.id);
                                        }
                                    "
                                    x-on:blur="commitBookmarkTagDraft(bookmark.id)"
                                />

                                <datalist x-bind:id="`quran-bookmark-tags-suggestions-${bookmark.id}`">
                                    <template
                                        x-for="tagSuggestion in bookmarkTagSuggestions(bookmark.id)"
                                        :key="`quran-bookmark-tag-suggestion-${bookmark.id}-${tagSuggestion}`"
                                    >
                                        <option x-bind:value="tagSuggestion"></option>
                                    </template>
                                </datalist>
                            </div>
                        </td>
                        <td>
                            <div class="quran-manager-actions">
                                <button
                                    class="quran-manager-action-button"
                                    data-quran-bookmark-replace
                                    type="button"
                                    x-on:click="replaceBookmarkPage(bookmark.id)"
                                >
                                    {{ app_arabic_text('استبدال') }}
                                </button>
                                <button
                                    class="quran-manager-action-button quran-manager-action-button--danger"
                                    data-quran-bookmark-remove
                                    type="button"
                                    x-on:click="removeBookmark(bookmark.id)"
                                >
                                    {{ app_arabic_text('حذف') }}
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>
