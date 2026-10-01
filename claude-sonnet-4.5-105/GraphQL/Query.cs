using Microsoft.EntityFrameworkCore;
using HotChocolate.Authorization;
using DevPortal.Data;
using DevPortal.Models;

namespace DevPortal.GraphQL;

public class Query
{
    [Authorize]
    [UseProjection]
    [UseFiltering]
    [UseSorting]
    public IQueryable<Resource> GetResources([Service] ApplicationDbContext context)
    {
        return context.Resources;
    }

    [Authorize]
    public async Task<Resource?> GetResourceById(int id, [Service] ApplicationDbContext context)
    {
        return await context.Resources.FindAsync(id);
    }
}