using Microsoft.EntityFrameworkCore;
using RealtimeChat.Data;
using RealtimeChat.Models;

namespace RealtimeChat.Services;

public class ChatService : IChatService
{
    private readonly ChatDbContext _context;

    public ChatService(ChatDbContext context)
    {
        _context = context;
    }

    public async Task<List<RoomDto>> GetRoomsAsync()
    {
        return await _context.Rooms
            .Where(r => r.IsActive)
            .Include(r => r.CreatedBy)
            .Include(r => r.Members)
            .Select(r => new RoomDto
            {
                Id = r.Id,
                Name = r.Name,
                Description = r.Description,
                CreatedBy = r.CreatedBy.Username,
                CreatedAt = r.CreatedAt,
                MemberCount = r.Members.Count
            })
            .ToListAsync();
    }

    public async Task<RoomDto?> GetRoomAsync(int roomId)
    {
        var room = await _context.Rooms
            .Where(r => r.Id == roomId && r.IsActive)
            .Include(r => r.CreatedBy)
            .Include(r => r.Members)
            .FirstOrDefaultAsync();

        if (room == null) return null;

        return new RoomDto
        {
            Id = room.Id,
            Name = room.Name,
            Description = room.Description,
            CreatedBy = room.CreatedBy.Username,
            CreatedAt = room.CreatedAt,
            MemberCount = room.Members.Count
        };
    }

    public async Task<RoomDto?> CreateRoomAsync(int userId, CreateRoomRequest request)
    {
        var room = new Room
        {
            Name = request.Name,
            Description = request.Description,
            CreatedById = userId,
            CreatedAt = DateTime.UtcNow,
            IsActive = true
        };

        _context.Rooms.Add(room);
        await _context.SaveChangesAsync();

        var member = new RoomMember
        {
            RoomId = room.Id,
            UserId = userId,
            JoinedAt = DateTime.UtcNow,
            Role = "Owner"
        };

        _context.RoomMembers.Add(member);
        await _context.SaveChangesAsync();

        return await GetRoomAsync(room.Id);
    }

    public async Task<bool> JoinRoomAsync(int roomId, int userId)
    {
        var room = await _context.Rooms.FindAsync(roomId);
        if (room == null || !room.IsActive) return false;

        var existingMember = await _context.RoomMembers
            .FirstOrDefaultAsync(rm => rm.RoomId == roomId && rm.UserId == userId);

        if (existingMember != null) return true;

        var member = new RoomMember
        {
            RoomId = roomId,
            UserId = userId,
            JoinedAt = DateTime.UtcNow,
            Role = "Member"
        };

        _context.RoomMembers.Add(member);
        await _context.SaveChangesAsync();
        return true;
    }

    public async Task<bool> LeaveRoomAsync(int roomId, int userId)
    {
        var member = await _context.RoomMembers
            .FirstOrDefaultAsync(rm => rm.RoomId == roomId && rm.UserId == userId);

        if (member == null) return false;

        _context.RoomMembers.Remove(member);
        await _context.SaveChangesAsync();
        return true;
    }

    public async Task<List<MessageDto>> GetMessagesAsync(int roomId, int userId)
    {
        var isMember = await _context.RoomMembers
            .AnyAsync(rm => rm.RoomId == roomId && rm.UserId == userId);

        if (!isMember) return new List<MessageDto>();

        return await _context.Messages
            .Where(m => m.RoomId == roomId)
            .Include(m => m.User)
            .OrderBy(m => m.SentAt)
            .Select(m => new MessageDto
            {
                Id = m.Id,
                Content = m.Content,
                Username = m.User.Username,
                SentAt = m.SentAt,
                IsEdited = m.IsEdited
            })
            .ToListAsync();
    }

    public async Task<MessageDto?> SendMessageAsync(int roomId, int userId, SendMessageRequest request)
    {
        var isMember = await _context.RoomMembers
            .AnyAsync(rm => rm.RoomId == roomId && rm.UserId == userId);

        if (!isMember) return null;

        var message = new Message
        {
            Content = request.Content,
            UserId = userId,
            RoomId = roomId,
            SentAt = DateTime.UtcNow,
            IsEdited = false
        };

        _context.Messages.Add(message);
        await _context.SaveChangesAsync();

        var user = await _context.Users.FindAsync(userId);
        return new MessageDto
        {
            Id = message.Id,
            Content = message.Content,
            Username = user?.Username ?? "Unknown",
            SentAt = message.SentAt,
            IsEdited = message.IsEdited
        };
    }
}