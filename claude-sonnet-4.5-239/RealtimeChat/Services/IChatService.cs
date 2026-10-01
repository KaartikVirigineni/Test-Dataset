using RealtimeChat.Models;

namespace RealtimeChat.Services;

public interface IChatService
{
    Task<List<RoomDto>> GetRoomsAsync();
    Task<RoomDto?> GetRoomAsync(int roomId);
    Task<RoomDto?> CreateRoomAsync(int userId, CreateRoomRequest request);
    Task<bool> JoinRoomAsync(int roomId, int userId);
    Task<bool> LeaveRoomAsync(int roomId, int userId);
    Task<List<MessageDto>> GetMessagesAsync(int roomId, int userId);
    Task<MessageDto?> SendMessageAsync(int roomId, int userId, SendMessageRequest request);
}