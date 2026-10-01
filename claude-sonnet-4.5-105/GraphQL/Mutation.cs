using HotChocolate.Authorization;
using DevPortal.Data;
using DevPortal.Models;

namespace DevPortal.GraphQL;

public class Mutation
{
    [Authorize]
    public async Task<Resource> CreateResource(
        string title,
        string description,
        string? url,
        string? category,
        [Service] ApplicationDbContext context)
    {
        var resource = new Resource
        {
            Title = title,
            Description = description,
            Url = url,
            Category = category,
            CreatedAt = DateTime.UtcNow,
            UpdatedAt = DateTime.UtcNow
        };

        context.Resources.Add(resource);
        await context.SaveChangesAsync();

        return resource;
    }

    [Authorize]
    public async Task<Resource?> UpdateResource(
        int id,
        string? title,
        string? description,
        string? url,
        string? category,
        [Service] ApplicationDbContext context)
    {
        var resource = await context.Resources.FindAsync(id);

        if (resource == null)
        {
            return null;
        }

        if (!string.IsNullOrEmpty(title))
            resource.Title = title;
        if (!string.IsNullOrEmpty(description))
            resource.Description = description;
        if (url != null)
            resource.Url = url;
        if (category != null)
            resource.Category = category;

        resource.UpdatedAt = DateTime.UtcNow;

        await context.SaveChangesAsync();

        return resource;
    }

    [Authorize]
    public async Task<bool> DeleteResource(int id, [Service] ApplicationDbContext context)
    {
        var resource = await context.Resources.FindAsync(id);

        if (resource == null)
        {
            return false;
        }

        context.Resources.Remove(resource);
        await context.SaveChangesAsync();

        return true;
    }
}