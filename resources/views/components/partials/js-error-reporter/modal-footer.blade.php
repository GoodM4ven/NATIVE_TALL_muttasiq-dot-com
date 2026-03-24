@props(['action'])

<div class="w-full pt-2">
    <hr class="bg-linear-to-r h-px border-0 from-transparent via-gray-300 to-transparent dark:via-gray-600">

    <div class="mt-5 text-center">
        {{ $action->getModalAction('openGithubIssue') }}
    </div>
</div>
