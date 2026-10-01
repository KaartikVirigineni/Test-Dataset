using Microsoft.EntityFrameworkCore;
using DevPortal.Data;
using DevPortal.Models;
using DevPortal.Models.DTOs;

namespace DevPortal.Services;

public class ProjectService : IProjectService
{
    private readonly AppDbContext _context;

    public ProjectService(AppDbContext context)
    {
        _context = context;
    }

    public async Task<Project?> CreateProject(CreateProjectRequest request, int userId)
    {
        var project = new Project
        {
            Name = request.Name,
            Description = request.Description,
            OwnerId = userId,
            CreatedAt = DateTime.UtcNow
        };

        _context.Projects.Add(project);
        await _context.SaveChangesAsync();

        return project;
    }

    public async Task<List<Project>> GetUserProjects(int userId)
    {
        return await _context.Projects
            .Where(p => p.OwnerId == userId)
            .Include(p => p.ApiKeys)
            .ToListAsync();
    }

    public async Task<Project?> GetProject(int id, int userId)
    {
        return await _context.Projects
            .Include(p => p.ApiKeys)
            .FirstOrDefaultAsync(p => p.Id == id && p.OwnerId == userId);
    }

    public async Task<bool> DeleteProject(int id, int userId)
    {
        var project = await _context.Projects.FirstOrDefaultAsync(p => p.Id == id && p.OwnerId == userId);
        
        if (project == null)
        {
            return false;
        }

        _context.Projects.Remove(project);
        await _context.SaveChangesAsync();
        return true;
    }
}