using FreelanceHub.Data;
using FreelanceHub.Models;
using HotChocolate.Authorization;
using Microsoft.EntityFrameworkCore;

namespace FreelanceHub.GraphQL;

public class Query
{
    [UseProjection]
    [UseFiltering]
    [UseSorting]
    public IQueryable<Project> GetProjects([Service] AppDbContext context)
    {
        return context.Projects.Include(p => p.Owner).Include(p => p.Bids);
    }

    [UseProjection]
    public IQueryable<Project> GetProject([Service] AppDbContext context, int id)
    {
        return context.Projects
            .Include(p => p.Owner)
            .Include(p => p.Bids)
            .ThenInclude(b => b.Bidder)
            .Where(p => p.Id == id);
    }

    [Authorize]
    [UseProjection]
    [UseFiltering]
    [UseSorting]
    public IQueryable<User> GetUsers([Service] AppDbContext context)
    {
        return context.Users
            .Include(u => u.Projects)
            .Include(u => u.Bids);
    }

    [UseProjection]
    [UseFiltering]
    [UseSorting]
    public IQueryable<Bid> GetBids([Service] AppDbContext context, int projectId)
    {
        return context.Bids
            .Include(b => b.Bidder)
            .Where(b => b.ProjectId == projectId);
    }
}