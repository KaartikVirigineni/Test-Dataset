using LogViewLite.Data;
using LogViewLite.Models;
using LogViewLite.Models.DTOs;
using Microsoft.EntityFrameworkCore;

namespace LogViewLite.Services;

public class LogService : ILogService
{
    private readonly ApplicationDbContext _context;

    public LogService(ApplicationDbContext context)
    {
        _context = context;
    }

    public async Task<LogEntry> CreateLogAsync(CreateLogRequest request)
    {
        var logEntry = new LogEntry
        {
            Level = request.Level,
            Source = request.Source,
            Message = request.Message,
            Metadata = request.Metadata,
            Timestamp = DateTime.UtcNow
        };

        _context.LogEntries.Add(logEntry);
        await _context.SaveChangesAsync();

        return logEntry;
    }

    public async Task<(List<LogEntry> logs, int total)> QueryLogsAsync(LogQueryRequest request)
    {
        var query = _context.LogEntries.AsQueryable();

        if (request.StartDate.HasValue)
        {
            query = query.Where(l => l.Timestamp >= request.StartDate.Value);
        }

        if (request.EndDate.HasValue)
        {
            query = query.Where(l => l.Timestamp <= request.EndDate.Value);
        }

        if (!string.IsNullOrWhiteSpace(request.Level))
        {
            query = query.Where(l => l.Level == request.Level);
        }

        if (!string.IsNullOrWhiteSpace(request.Source))
        {
            query = query.Where(l => l.Source.Contains(request.Source));
        }

        if (!string.IsNullOrWhiteSpace(request.SearchText))
        {
            query = query.Where(l => l.Message.Contains(request.SearchText));
        }

        var total = await query.CountAsync();

        var logs = await query
            .OrderByDescending(l => l.Timestamp)
            .Skip((request.Page - 1) * request.PageSize)
            .Take(request.PageSize)
            .ToListAsync();

        return (logs, total);
    }

    public async Task<LogEntry?> GetLogByIdAsync(int id)
    {
        return await _context.LogEntries.FindAsync(id);
    }

    public async Task DeleteLogAsync(int id)
    {
        var log = await _context.LogEntries.FindAsync(id);
        if (log != null)
        {
            _context.LogEntries.Remove(log);
            await _context.SaveChangesAsync();
        }
    }

    public async Task<Dictionary<string, int>> GetLogStatisticsAsync()
    {
        var stats = await _context.LogEntries
            .GroupBy(l => l.Level)
            .Select(g => new { Level = g.Key, Count = g.Count() })
            .ToDictionaryAsync(x => x.Level, x => x.Count);

        return stats;
    }
}