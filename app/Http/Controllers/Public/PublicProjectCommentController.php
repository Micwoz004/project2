<?php

namespace App\Http\Controllers\Public;

use App\Domain\Communications\Actions\AddProjectPublicCommentAction;
use App\Domain\Communications\Actions\EditProjectPublicCommentAction;
use App\Domain\Communications\Actions\ToggleProjectPublicCommentHiddenAction;
use App\Domain\Communications\Models\ProjectPublicComment;
use App\Domain\Projects\Models\Project;
use App\Http\Controllers\Controller;
use App\Models\User;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class PublicProjectCommentController extends Controller
{
    public function store(Project $project, Request $request, AddProjectPublicCommentAction $addProjectPublicComment): RedirectResponse
    {
        Log::info('public_project_comment.store.start', [
            'project_id' => $project->id,
            'user_id' => $request->user()?->id,
        ]);

        Gate::authorize('view', $project);
        $user = $this->actor($request);
        $data = $request->validate([
            'content' => ['required', 'string', 'max:200'],
            'parent_id' => ['nullable', 'integer', 'exists:project_public_comments,id'],
        ]);
        $parent = $this->parentComment($project, $data['parent_id'] ?? null);

        try {
            $comment = $addProjectPublicComment->execute($project, $user, $data['content'], $parent);
        } catch (DomainException $exception) {
            Log::warning('public_project_comment.store.rejected', [
                'project_id' => $project->id,
                'user_id' => $user->id,
                'reason' => $exception->getMessage(),
            ]);

            return back()->withInput()->withErrors(['comment' => $exception->getMessage()]);
        }

        Log::info('public_project_comment.store.success', [
            'project_id' => $project->id,
            'comment_id' => $comment->id,
            'user_id' => $user->id,
        ]);

        return back()->with('status', 'Komentarz został dodany.');
    }

    public function update(
        Project $project,
        ProjectPublicComment $comment,
        Request $request,
        EditProjectPublicCommentAction $editProjectPublicComment,
    ): RedirectResponse {
        Log::info('public_project_comment.update.start', [
            'project_id' => $project->id,
            'comment_id' => $comment->id,
            'user_id' => $request->user()?->id,
        ]);

        $this->abortIfCommentOutsideProject($project, $comment);
        Gate::authorize('view', $project);
        $user = $this->actor($request);
        $data = $request->validate([
            'content' => ['required', 'string', 'max:200'],
        ]);

        try {
            $updated = $editProjectPublicComment->execute($comment, $user, $data['content']);
        } catch (DomainException $exception) {
            Log::warning('public_project_comment.update.rejected', [
                'project_id' => $project->id,
                'comment_id' => $comment->id,
                'user_id' => $user->id,
                'reason' => $exception->getMessage(),
            ]);

            return back()->withInput()->withErrors(['comment' => $exception->getMessage()]);
        }

        Log::info('public_project_comment.update.success', [
            'project_id' => $project->id,
            'comment_id' => $updated->id,
            'user_id' => $user->id,
        ]);

        return back()->with('status', 'Komentarz został zapisany.');
    }

    public function toggleHidden(
        Project $project,
        ProjectPublicComment $comment,
        Request $request,
        ToggleProjectPublicCommentHiddenAction $toggleProjectPublicCommentHidden,
    ): RedirectResponse {
        Log::info('public_project_comment.hide.toggle.start', [
            'project_id' => $project->id,
            'comment_id' => $comment->id,
            'user_id' => $request->user()?->id,
        ]);

        $this->abortIfCommentOutsideProject($project, $comment);
        Gate::authorize('view', $project);
        $user = $this->actor($request);

        try {
            $updated = $toggleProjectPublicCommentHidden->execute($comment, $user);
        } catch (DomainException $exception) {
            Log::warning('public_project_comment.hide.toggle.rejected', [
                'project_id' => $project->id,
                'comment_id' => $comment->id,
                'user_id' => $user->id,
                'reason' => $exception->getMessage(),
            ]);

            return back()->withErrors(['comment' => $exception->getMessage()]);
        }

        Log::info('public_project_comment.hide.toggle.success', [
            'project_id' => $project->id,
            'comment_id' => $updated->id,
            'user_id' => $user->id,
            'hidden' => $updated->hidden,
        ]);

        return back()->with('status', $updated->hidden ? 'Komentarz został ukryty.' : 'Komentarz został przywrócony.');
    }

    private function actor(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    private function parentComment(Project $project, ?int $parentId): ?ProjectPublicComment
    {
        if ($parentId === null) {
            return null;
        }

        $parent = ProjectPublicComment::query()->findOrFail($parentId);
        $this->abortIfCommentOutsideProject($project, $parent);

        return $parent;
    }

    private function abortIfCommentOutsideProject(Project $project, ProjectPublicComment $comment): void
    {
        abort_unless($comment->project_id === $project->id, 404);
    }
}
