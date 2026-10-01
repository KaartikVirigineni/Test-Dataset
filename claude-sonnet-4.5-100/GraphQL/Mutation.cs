using FreelanceHub.Data;
using FreelanceHub.Models;
using FreelanceHub.Services;
using HotChocolate.Authorization;
using Microsoft.EntityFrameworkCore;
using System.Security.Claims;

namespace FreelanceHub.GraphQL;

public class Mutation
{
    public async Task<AuthPayload> Register(
        [Service] AppDbContext context,
        [Service] IAuthService authService,
        string email,
        string password,
        string name)
    {
        if (await context.Users.AnyAsync(u => u.Email == email))
        {
            throw new GraphQLException("User already exists");
        }

        var user = new User
        {
            Email = email,
            PasswordHash = authService.HashPassword(password),
            Name = name
        };

        context.Users.Add(user);
        await context.SaveChangesAsync();

        var token = authService.GenerateToken(user.Id, user.Email);

        return new AuthPayload
        {
            Token = token,
            User = user
        };
    }

    public async Task<AuthPayload> Login(
        [Service] AppDbContext context,
        [Service] IAuthService authService,
        string email,
        string password)
    {
        var user = await context.Users.FirstOrDefaultAsync(u => u.Email == email);
        
        if (user == null || !authService.VerifyPassword(password, user.PasswordHash))
        {
            throw new GraphQLException("Invalid credentials");
        }

        var token = authService.GenerateToken(user.Id, user.Email);

        return new AuthPayload
        {
            Token = token,
            User = user
        };
    }

    [Authorize]
    public async Task<Project> CreateProject(
        [Service] AppDbContext context,
        ClaimsPrincipal claimsPrincipal,
        string title,
        string description,
        decimal budget)
    {
        var userIdClaim = claimsPrincipal.FindFirst(ClaimTypes.NameIdentifier)?.Value;
        if (userIdClaim == null || !int.TryParse(userIdClaim, out var userId))
        {
            throw new GraphQLException("Unauthorized");
        }

        var project = new Project
        {
            Title = title,
            Description = description,
            Budget = budget,
            OwnerId = userId,
            Status = "Open"
        };

        context.Projects.Add(project);
        await context.SaveChangesAsync();

        return project;
    }

    [Authorize]
    public async Task<Bid> CreateBid(
        [Service] AppDbContext context,
        ClaimsPrincipal claimsPrincipal,
        int projectId,
        decimal amount,
        string proposal)
    {
        var userIdClaim = claimsPrincipal.FindFirst(ClaimTypes.NameIdentifier)?.Value;
        if (userIdClaim == null || !int.TryParse(userIdClaim, out var userId))
        {
            throw new GraphQLException("Unauthorized");
        }

        var project = await context.Projects.FindAsync(projectId);
        if (project == null)
        {
            throw new GraphQLException("Project not found");
        }

        if (project.OwnerId == userId)
        {
            throw new GraphQLException("Cannot bid on your own project");
        }

        var bid = new Bid
        {
            ProjectId = projectId,
            BidderId = userId,
            Amount = amount,
            Proposal = proposal
        };

        context.Bids.Add(bid);
        await context.SaveChangesAsync();

        return bid;
    }

    [Authorize]
    public async Task<Project> UpdateProjectStatus(
        [Service] AppDbContext context,
        ClaimsPrincipal claimsPrincipal,
        int projectId,
        string status)
    {
        var userIdClaim = claimsPrincipal.FindFirst(ClaimTypes.NameIdentifier)?.Value;
        if (userIdClaim == null || !int.TryParse(userIdClaim, out var userId))
        {
            throw new GraphQLException("Unauthorized");
        }

        var project = await context.Projects.FindAsync(projectId);
        if (project == null)
        {
            throw new GraphQLException("Project not found");
        }

        if (project.OwnerId != userId)
        {
            throw new GraphQLException("Only project owner can update status");
        }

        project.Status = status;
        await context.SaveChangesAsync();

        return project;
    }
}

public class AuthPayload
{
    public string Token { get; set; } = string.Empty;
    public User User { get; set; } = null!;
}