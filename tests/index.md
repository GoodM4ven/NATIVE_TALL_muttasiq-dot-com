# Skipped Tests

Generated on 2026-05-31 after full non-browser and browser test runs.

- adds extra spacing only under the al-fatiha surah header
- applies surah-affix rules correctly in word-target drag mode
- clears search results immediately after closing the search modal
- keeps dense-to-headered jump-page navigation to a single visible reveal on mobile
- keeps modal jump-page header and basmallah geometry aligned with a subsequent regular render on mobile
- keeps quran reader stable for layout, slider navigation, and modal refit timing
- keeps the shared top counter full briefly, pulses it, then resets it after auto-advance
- keeps quran text fitted and visible across all reader navigation paths
- keeps wird committed progress monotonic and completion badge sticky while browsing back
- lands on the final wird slider page and keeps the re-entered completed page visible
- persists local reader state for last page, navigation history, and bookmarks
- resolves a canonical navigation target for local quran search preview rows
- restores the prior page and keeps it rendered when exiting wird after rapid navigation
- handles athkar notice selection, confirmation/swipe transitions, and restored mobile back flow
- swipes only navigate without counting when setting 2 is disabled
- navigates to the athkar gate, persists restored state, and handles native back to main menu then exit
- shows tap aura on mobile for single-count auto-advance athkar
- executes hidden completion buttons on desktop for single thikr and all athkar
- publishes android bundles from the bundled credentials