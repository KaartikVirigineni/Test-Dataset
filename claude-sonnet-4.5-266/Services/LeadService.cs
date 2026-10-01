using Microsoft.EntityFrameworkCore;
using LeadScorePro.Data;
using LeadScorePro.Models;
using LeadScorePro.Models.DTOs;

namespace LeadScorePro.Services;

public class LeadService : ILeadService
{
    private readonly AppDbContext _context;

    public LeadService(AppDbContext context)
    {
        _context = context;
    }

    public async Task<IEnumerable<Lead>> GetAllLeadsAsync()
    {
        return await _context.Leads
            .Include(l => l.Activities)
            .OrderByDescending(l => l.CreatedAt)
            .ToListAsync();
    }

    public async Task<Lead?> GetLeadByIdAsync(int id)
    {
        return await _context.Leads
            .Include(l => l.Activities)
            .FirstOrDefaultAsync(l => l.Id == id);
    }

    public async Task<Lead> CreateLeadAsync(CreateLeadRequest request)
    {
        var lead = new Lead
        {
            FirstName = request.FirstName,
            LastName = request.LastName,
            Email = request.Email,
            Company = request.Company,
            Phone = request.Phone,
            Source = request.Source,
            Status = "New",
            Score = 0,
            CreatedAt = DateTime.UtcNow
        };

        _context.Leads.Add(lead);
        await _context.SaveChangesAsync();

        return lead;
    }

    public async Task<Lead?> UpdateLeadAsync(int id, UpdateLeadRequest request)
    {
        var lead = await _context.Leads.FindAsync(id);
        if (lead == null)
        {
            return null;
        }

        if (!string.IsNullOrWhiteSpace(request.FirstName))
            lead.FirstName = request.FirstName;
        
        if (!string.IsNullOrWhiteSpace(request.LastName))
            lead.LastName = request.LastName;
        
        if (!string.IsNullOrWhiteSpace(request.Email))
            lead.Email = request.Email;
        
        if (request.Company != null)
            lead.Company = request.Company;
        
        if (request.Phone != null)
            lead.Phone = request.Phone;
        
        if (!string.IsNullOrWhiteSpace(request.Status))
            lead.Status = request.Status;
        
        if (request.Score.HasValue)
            lead.Score = request.Score.Value;

        await _context.SaveChangesAsync();
        return lead;
    }

    public async Task<bool> DeleteLeadAsync(int id)
    {
        var lead = await _context.Leads.FindAsync(id);
        if (lead == null)
        {
            return false;
        }

        _context.Leads.Remove(lead);
        await _context.SaveChangesAsync();
        return true;
    }

    public async Task<Activity?> AddActivityAsync(int leadId, CreateActivityRequest request)
    {
        var lead = await _context.Leads.FindAsync(leadId);
        if (lead == null)
        {
            return null;
        }

        var activity = new Activity
        {
            LeadId = leadId,
            Type = request.Type,
            Description = request.Description,
            ScoreImpact = request.ScoreImpact,
            CreatedAt = DateTime.UtcNow
        };

        lead.Score += request.ScoreImpact;
        lead.LastContactedAt = DateTime.UtcNow;

        _context.Activities.Add(activity);
        await _context.SaveChangesAsync();

        return activity;
    }

    public async Task<IEnumerable<Activity>> GetLeadActivitiesAsync(int leadId)
    {
        return await _context.Activities
            .Where(a => a.LeadId == leadId)
            .OrderByDescending(a => a.CreatedAt)
            .ToListAsync();
    }
}