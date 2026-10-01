using DevPortal.Models;
using DevPortal.Models.DTOs;

namespace DevPortal.Services;

public interface IProjectService
{
    Task<Project?> CreateProject(CreateProjectRequest request, int userId);
    Task<List<Project>> GetUserProjects(int userId);
    Task<Project?> GetProject(int id, int userId);
    Task<bool> DeleteProject(int id, int userId);
}