using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using RealtimeChat.Models;
using RealtimeChat.Services;
using System.Security.Claims;

namespace RealtimeChat.Controllers;

[ApiController]
[Route("api/[controller]")]
[Authorize(Policy = "UserOrAdmin")]
public class RoomsController : ControllerBase
{
    private readonly IChatService _chatService;

    public RoomsController(IChatService chatService)
    {
        _chatService = chatService;
    }

    [HttpGet]
    public async Task<IActionResult> GetRooms()
    {
        var rooms = await _chatService.GetRoomsAsync();
        return Ok(rooms);
    }

    [HttpGet("{id}")]
    public async Task<IActionResult> GetRoom(int id)
    {
        var room = await _chatService.GetRoomAsync(id);
        if (room == null)
        {
            return NotFound(new { message = "Room not found" });
        }

        return Ok(room);
    }

    [HttpPost]
    public async Task<IActionResult> CreateRoom([FromBody] CreateRoomRequest request)
    {
        var userId = int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier)!);
        var room = await _chatService.CreateRoomAsync(userId, request);
        
        if (room == null)
        {
            return BadRequest(new { message = "Failed to create room" });
        }

        return CreatedAtAction(nameof(GetRoom), new { id = room.Id }, room);
    }

    [HttpPost("{id}/join")]
    public async Task<IActionResult> JoinRoom(int id)
    {
        var userId = int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier)!);
        var result = await _chatService.JoinRoomAsync(id, userId);
        
        if (!result)
        {
            return BadRequest(new { message = "Failed to join room" });
        }

        return Ok(new { message = "Successfully joined room" });
    }

    [HttpPost("{id}/leave")]
    public async Task<IActionResult> LeaveRoom(int id)
    {
        var userId = int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier)!);
        var result = await _chatService.LeaveRoomAsync(id, userId);
        
        if (!result)
        {
            return BadRequest(new { message = "Failed to leave room" });
        }

        return Ok(new { message = "Successfully left room" });
    }
}