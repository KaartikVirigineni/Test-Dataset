using LogViewLite.Models;
using LogViewLite.Models.DTOs;

namespace LogViewLite.Services;

public interface ILogService
{
    Task<LogEntry> CreateLogAsync(CreateLogRequest request);
    Task<(List<LogEntry> logs, int total)> QueryLogsAsync(LogQueryRequest request);
    Task<LogEntry?> GetLogByIdAsync(int id);
    Task DeleteLogAsync(int id);
    Task<Dictionary<string, int>> GetLogStatisticsAsync();
}