using LeadScorePro.Models;
using LeadScorePro.Models.DTOs;

namespace LeadScorePro.Services;

public interface ILeadService
{
    Task<IEnumerable<Lead>> GetAllLeadsAsync();
    Task<Lead?> GetLeadByIdAsync(int id);
    Task<Lead> CreateLeadAsync(CreateLeadRequest request);
    Task<Lead?> UpdateLeadAsync(int id, UpdateLeadRequest request);
    Task<bool> DeleteLeadAsync(int id);
    Task<Activity?> AddActivityAsync(int leadId, CreateActivityRequest request);
    Task<IEnumerable<Activity>> GetLeadActivitiesAsync(int leadId);
}