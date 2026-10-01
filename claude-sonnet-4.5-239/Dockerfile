FROM mcr.microsoft.com/dotnet/sdk:8.0 AS build
WORKDIR /src

COPY RealtimeChat.sln ./
COPY RealtimeChat/RealtimeChat.csproj RealtimeChat/
RUN dotnet restore

COPY . .
WORKDIR /src/RealtimeChat
RUN dotnet build -c Release -o /app/build

FROM build AS publish
RUN dotnet publish -c Release -o /app/publish

FROM mcr.microsoft.com/dotnet/aspnet:8.0 AS final
WORKDIR /app
COPY --from=publish /app/publish .
COPY openapi.yaml /app/openapi.yaml

ENV ASPNETCORE_URLS=http://+:8000
EXPOSE 8000

ENTRYPOINT ["dotnet", "RealtimeChat.dll"]