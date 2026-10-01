using Microsoft.EntityFrameworkCore;
using DevPortal.Data;
using DevPortal.Models;
using DevPortal.Models.DTOs;

namespace DevPortal.Services;

public class ApiKeyService : IApiKeyService
{
    private readonly AppDbContext _context;

    public ApiKeyService(AppDbContext context)
    {
        _context = context;
    }

    public async Task<ApiKey?> CreateApiKey(int projectId, CreateApiKeyRequest request, int userId)
    {
        var project = await _context.Projects.FirstOrDefaultAsync(p => p.Id == projectId && p.OwnerId == userId);
        
        if (project == null)
        {
            return null;
        }

        var key = GenerateApiKey();
        
        var apiKey = new ApiKey
        {
            Key = key,
            Name = request.Name,
            ProjectId = projectId,
            IsActive = true,
            CreatedAt = DateTime.UtcNow,
            ExpiresAt = request.ExpiryInDays.HasValue ? DateTime.UtcNow.AddDays(request.ExpiryInDays.Value) : null
        };

        _context.ApiKeys.Add(apiKey);
        await _context.SaveChangesAsync();

        return apiKey;
    }

    public async Task<bool> RevokeApiKey(int id, int userId)
    {
        var apiKey = await _context.ApiKeys
            .Include(a => a.Project)
            .FirstOrDefaultAsync(a => a.Id == id && a.Project.OwnerId == userId);
        
        if (apiKey == null)
        {
            return false;
        }

        apiKey.IsActive = false;
        await _context.SaveChangesAsync();
        return true;
    }

    public async Task<List<ApiKey>> GetProjectApiKeys(int projectId, int userId)
    {
        var project = await _context.Projects.FirstOrDefaultAsync(p => p.Id == projectId && p.OwnerId == userId);
        
        if (project == null)
        {
            return new List<ApiKey>();
        }

        return await _context.ApiKeys
            .Where(a => a.ProjectId == projectId)
            .ToListAsync();
    }

    private string GenerateApiKey()
    {
        return $"dpk_{Guid.NewGuid():N}{Guid.NewGuid():N}".Substring(0, 48);
    }
}